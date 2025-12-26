<script setup>
import { ref, nextTick, watch } from 'vue';

const isOpen = ref(false);
const messages = ref([
    { role: 'assistant', content: 'Hello! I am your dental assistant. I can help you with questions, create patient records, and book appointments. How can I help you today?' }
]);
const newMessage = ref('');
const isLoading = ref(false);
const messagesContainer = ref(null);
const pendingAction = ref(null);

const toggleChat = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        scrollToBottom();
    }
};

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const validateActionData = function(messageData) {
    if (messageData.action === 'create_patient') {
        return messageData.data.name && 
               messageData.data.name.trim() !== '' &&
               messageData.data.phone && 
               messageData.data.phone.trim() !== '';
    } else if (messageData.action === 'book_appointment') {
        return messageData.data.patient_name && 
               messageData.data.patient_name.trim() !== '' &&
               messageData.data.date && 
               messageData.data.date.trim() !== '' &&
               messageData.data.time && 
               messageData.data.time.trim() !== '' &&
               messageData.data.type && 
               messageData.data.type.trim() !== '';
    }
    return false;
};

const parseNaturalDate = (dateInput) => {
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(today.getDate() + 1);
    
    const normalizedInput = dateInput.toLowerCase().trim();
    
    // Handle combined input like "tomorrow 2pm" by extracting just the date part
    let datePart = normalizedInput;
    const timeKeywords = ['am', 'pm', ':'];
    for (const keyword of timeKeywords) {
        if (normalizedInput.includes(keyword)) {
            // Split on time keyword and take the first part as date
            datePart = normalizedInput.split(keyword)[0].trim();
            break;
        }
    }
    
    // Handle common typos and variations
    const dateVariations = {
        'today': today,
        'tomorrow': tomorrow,
        'tmrw': tomorrow,
        'tomorrw': tomorrow,
        'tommorrow': tomorrow,
        'next week': new Date(today.getTime() + (7 * 24 * 60 * 60 * 1000)),
        'next wk': new Date(today.getTime() + (7 * 24 * 60 * 60 * 1000)),
        'yesterday': null, // Don't allow past dates
    };
    
    // Helper function to format date as DD-MM-YYYY with dashes
    const formatDateDDMMYYYY = (date) => {
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}-${month}-${year}`;
    };

    // Handle common natural language dates and typos
    switch (datePart) {
        case 'today':
            return formatDateDDMMYYYY(today);
        case 'tomorrow':
        case 'tmrw':
        case 'tomorrw':
        case 'tommorrow':
            return formatDateDDMMYYYY(tomorrow);
        case 'next week':
        case 'next wk':
            return formatDateDDMMYYYY(dateVariations['next week']);
            nextWeek.setDate(today.getDate() + 7);
            return formatDateDDMMYYYY(nextWeek);
        case 'yesterday':
            return null; // Don't allow past dates
    }
    
    // Try to parse as regular date (DD-MM-YYYY format)
    // First try DD-MM-YYYY format
    const ddmmyyyyRegex = /^(\d{2})-(\d{2})-(\d{4})$/;
    const match = dateInput.match(ddmmyyyyRegex);
    let parsedDate;
    
    if (match) {
        // Parse DD-MM-YYYY format
        parsedDate = new Date(`${match[3]}-${match[2]}-${match[1]}`); // Convert to YYYY-MM-DD for Date constructor
    } else {
        // Fallback to default Date parsing
        parsedDate = new Date(dateInput);
    }
    
    if (!isNaN(parsedDate) && parsedDate >= today) {
        return formatDateDDMMYYYY(parsedDate);
    }
    
    // If parsing failed or date is in the past, return null
    return null;
};

const executeAction = async (actionData) => {
    try {
        let endpoint = '';
        let data = {};

        if (actionData.action === 'create_patient') {
            endpoint = '/api/chat/patient';
            data = {
                name: actionData.data.name || '',
                email: actionData.data.email || '',
                phone: actionData.data.phone || '',
                dob: actionData.data.dob || '',
                age: actionData.data.age || '',
                address: actionData.data.address || '',
            };
        } else if (actionData.action === 'create_patient_for_appointment') {
            // Start collecting patient information for appointment booking
            collectingInfo.value = true;
            currentForm.value = 'patient_for_appointment';
            appointmentData.value = actionData.data.appointment_data;
            
            // Initialize patientData with empty object to ensure clean state
            patientData.value = {
                name: '',
                email: '',
                phone: '',
                dob: '',
                address: ''
            };
            
            await collectPatientInfo('name');
            return;
        } else if (actionData.action === 'book_appointment') {
            endpoint = '/api/chat/appointment';
            
            // Parse the date to handle natural language like "tomorrow"
            const parsedDate = parseNaturalDate(actionData.data.date || '');
            if (!parsedDate) {
                throw new Error('Invalid date. Please provide a valid future date like "tomorrow", "next week", or a specific date (DD-MM-YYYY).');
            }
            
            data = {
                patient_name: actionData.data.patient_name || '',
                date: parsedDate,
                time: actionData.data.time || '',
                type: actionData.data.type || '',
                reason: actionData.data.reason || '',
            };
        }

        const response = await window.axios.post(endpoint, data);
        
        if (response.data.success) {
            messages.value.push({ 
                role: 'assistant', 
                content: response.data.message || 'Action completed successfully.',
                type: 'success'
            });
        } else {
            let errorMessage = response.data.message || 'An error occurred while processing your request.';
            
            // Handle specific error cases
            if (response.status === 409) {
                errorMessage = 'This patient already exists in our system. Please check the details and try again.';
            } else if (response.status === 404 && response.data.action === 'create_patient') {
                // Patient not found, automatically start patient registration
                const patientName = actionData.data.patient_name;
                const messageIndex = messages.value.push({
                    role: 'assistant',
                    content: `I couldn't find a patient named "${patientName}" in our system. Let's create a patient record for them first.`,
                    structured: true,
                    data: {
                        action: 'create_patient_for_appointment',
                        data: {
                            patient_name: patientName,
                            appointment_data: actionData.data
                        },
                        message: `Create patient record for "${patientName}" and then book the appointment`
                    },
                    showButtons: true
                }) - 1;
                
                // Set pending action
                pendingAction.value = {
                    action: 'create_patient_for_appointment',
                    data: {
                        patient_name: patientName,
                        appointment_data: actionData.data
                    },
                    messageIndex
                };
                return; // Don't show the error message since we're offering to create patient
            }
            
            messages.value.push({ 
                role: 'assistant', 
                content: errorMessage,
                type: 'error'
            });
        }
    } catch (error) {
        console.error('Action execution error:', error);
        
        let errorMessage = 'I encountered an error while processing your request. ';
        
        if (error.response) {
            // Handle 422 Validation Errors
            if (error.response.status === 422 && error.response.data.errors) {
                const errors = error.response.data.errors;
                errorMessage += 'Please check the following fields:\n';
                Object.keys(errors).forEach(field => {
                    errorMessage += `- ${field}: ${errors[field].join(' ')}\n`;
                });
            } 
            // Handle other HTTP errors
            else if (error.response.data.message) {
                errorMessage += error.response.data.message;
            } else {
                errorMessage += `(Status: ${error.response.status})`;
            }
        }
        
        messages.value.push({ 
            role: 'assistant', 
            content: errorMessage,
            type: 'error'
        });
        
        // Re-enable buttons if needed
        if (pendingAction.value) {
            const messageIndex = pendingAction.value.messageIndex;
            if (messageIndex !== undefined && messages.value[messageIndex]) {
                messages.value[messageIndex].showButtons = true;
                messages.value = [...messages.value];
            }
        }
        
        pendingAction.value = null;
        scrollToBottom();
    }
};

const confirmAction = async () => {
    if (pendingAction.value) {
        try {
            // Create a copy of the pending action to avoid reference issues
            const actionToExecute = { ...pendingAction.value };
            
            // Clear the pending action and confirmation state
            const messageIndex = pendingAction.value.messageIndex;
            if (messageIndex !== undefined && messages.value[messageIndex]) {
                messages.value[messageIndex].showButtons = false;
                messages.value[messageIndex].content += '\n\nProcessing...';
                messages.value = [...messages.value];
            }
            
            // Clear the pending action
            pendingAction.value = null;
            
            // Execute the action
            await executeAction(actionToExecute);
            
            // Reset form data after successful submission
            if (actionToExecute.action === 'create_patient') {
                patientData.value = {
                    name: '',
                    email: '',
                    phone: '',
                    dob: '',
                    address: ''
                };
            } else if (actionToExecute.action === 'book_appointment') {
                appointmentData.value = {
                    patient_name: '',
                    date: '',
                    time: '',
                    type: 'checkup',
                    notes: ''
                };
            }
        } catch (error) {
            console.error('Error in confirmAction:', error);
            
            // Re-enable buttons if there was an error
            const messageIndex = pendingAction.value?.messageIndex;
            if (messageIndex !== undefined && messages.value[messageIndex]) {
                messages.value[messageIndex].showButtons = true;
                messages.value = [...messages.value];
            }
        }
    }
};

const cancelAction = () => {
    if (pendingAction.value) {
        const messageIndex = pendingAction.value.messageIndex;
        if (messageIndex !== undefined && messages.value[messageIndex]) {
            messages.value[messageIndex].showButtons = false;
        }
        
        // Reset all collection states
        collectingInfo.value = false;
        currentField.value = '';
        pendingAction.value = null;
        
        messages.value.push({ 
            role: 'assistant', 
            content: 'Action cancelled. Is there anything else I can help you with?' 
        });
        
        scrollToBottom();
    }
};

// Track form collection state
const collectingInfo = ref(false);
const currentField = ref('');
const currentForm = ref(''); // 'patient' or 'appointment'

// Form data
const patientData = ref({
    name: '',
    email: '',
    phone: '',
    dob: '',
    address: ''
});

const appointmentData = ref({
    patient_name: '',
    date: '',
    time: '',
    type: 'checkup',
    notes: ''
});

const appointmentTypes = [
    { value: 'checkup', label: 'Routine Checkup' },
    { value: 'cleaning', label: 'Teeth Cleaning' },
    { value: 'filling', label: 'Filling' },
    { value: 'extraction', label: 'Tooth Extraction' },
    { value: 'other', label: 'Other' }
];

const collectAppointmentInfo = async (field) => {
    currentField.value = field;
    let prompt = '';
    
    switch(field) {
        case 'patient_name':
            prompt = 'Please provide the patient\'s full name:';
            break;
        case 'date':
            prompt = 'What date would you like to book the appointment for? (DD-MM-YYYY):';
            break;
        case 'time':
            prompt = 'What time would you like to book the appointment for? (HH:MM AM/PM):';
            break;
        case 'type':
            const typeOptions = appointmentTypes.map(t => `- ${t.label} (${t.value})`).join('\n');
            prompt = `What type of appointment is this? Please choose one:\n${typeOptions}`;
            break;
        case 'notes':
            prompt = 'Any additional notes or special requirements for this appointment?';
            break;
    }
    
    messages.value.push({
        role: 'assistant',
        content: prompt,
        isPrompt: true
    });
    scrollToBottom();
};

const collectPatientInfo = async (field) => {
    currentField.value = field;
    let prompt = '';
    
    switch(field) {
        case 'name':
            prompt = 'Please provide the patient\'s full name:';
            break;
        case 'email':
            prompt = 'Please provide the patient\'s email address:';
            break;
        case 'phone':
            prompt = 'Please provide the patient\'s phone number:';
            break;
        case 'dob':
            prompt = 'Please provide the patient\'s date of birth (DD-MM-YYYY) or age:';
            break;
        case 'address':
            prompt = 'Please provide the patient\'s full address:';
            break;
    }
    
    messages.value.push({
        role: 'assistant',
        content: prompt,
        isPrompt: true
    });
    scrollToBottom();
};

const processUserInput = async (userInput) => {
    if (!collectingInfo.value || !currentField.value) return;
    
    try {
        // Store the user's input based on the current form
        if (currentForm.value === 'patient') {
            // Check if user provided age instead of DOB
            const normalizedInput = userInput.toLowerCase().trim();
            const isAgeProvided = (
                normalizedInput.match(/^\d+$/) || 
                normalizedInput.includes('years old') || 
                normalizedInput.includes('year old') || 
                normalizedInput.includes('yrs old')
            );
            
            if (currentField.value === 'dob' && isAgeProvided) {
                // User provided age, store in age field instead
                patientData.value.age = userInput;
                // Skip to next field since we got age
                const fields = ['name', 'email', 'phone', 'dob', 'address'];
                const currentIndex = fields.indexOf(currentField.value);
                if (currentIndex < fields.length - 1) {
                    await collectPatientInfo(fields[currentIndex + 1]);
                } else {
                    // Show confirmation
                    collectingInfo.value = false;
                    currentField.value = '';
                    // Format patient info for display
                    const patientInfo = `Please confirm the patient details:\n\n` +
                        `Name: ${patientData.value.name}\n` +
                        `Email: ${patientData.value.email}\n` +
                        `Phone: ${patientData.value.phone}\n` +
                        `Age: ${patientData.value.age}\n` +
                        `Address: ${patientData.value.address}`;
                    
                    const messageIndex = messages.value.push({
                        role: 'assistant',
                        content: patientInfo,
                        structured: true,
                        data: {
                            action: 'create_patient',
                            data: { ...patientData.value },
                            message: patientInfo
                        },
                        showButtons: true
                    }) - 1;
                    
                    pendingAction.value = {
                        action: 'create_patient',
                        data: { ...patientData.value },
                        messageIndex
                    };
                }
                return;
            }
            
            patientData.value[currentField.value] = userInput;
            
            // Determine next field or show confirmation
            const fields = ['name', 'email', 'phone', 'dob', 'address'];
            const currentIndex = fields.indexOf(currentField.value);
            
            if (currentIndex < fields.length - 1) {
                // Move to next field
                await collectPatientInfo(fields[currentIndex + 1]);
            } else {
                // All fields collected, show confirmation
                collectingInfo.value = false;
                currentField.value = '';
                
                // Format patient info for display
                const patientInfo = `Please confirm the patient details:\n\n` +
                    `Name: ${patientData.value.name}\n` +
                    `Email: ${patientData.value.email}\n` +
                    `Phone: ${patientData.value.phone}\n` +
                    `Date of Birth: ${patientData.value.dob}\n` +
                    `Address: ${patientData.value.address}`;
                
                const messageIndex = messages.value.push({
                    role: 'assistant',
                    content: patientInfo,
                    structured: true,
                    data: {
                        action: 'create_patient',
                        data: { ...patientData.value },
                        message: patientInfo
                    },
                    showButtons: true
                }) - 1;
                
                // Set pending action
                pendingAction.value = {
                    action: 'create_patient',
                    data: { ...patientData.value },
                    messageIndex
                };
            }
        } else if (currentForm.value === 'patient_for_appointment') {
            patientData.value[currentField.value] = userInput;
            
            // Determine next field or show confirmation
            const fields = ['name', 'email', 'phone', 'dob', 'address'];
            const currentIndex = fields.indexOf(currentField.value);
            
            if (currentIndex < fields.length - 1) {
                // Move to next field
                await collectPatientInfo(fields[currentIndex + 1]);
            } else {
                // All fields collected, create patient and then book appointment
                collectingInfo.value = false;
                currentField.value = '';
                
                // Create patient first
                try {
                    const patientResponse = await window.axios.post('/api/chat/patient', {
                        name: patientData.value.name,
                        email: patientData.value.email,
                        phone: patientData.value.phone,
                        dob: patientData.value.dob,
                        address: patientData.value.address
                    });
                    
                    if (patientResponse.data.success) {
                        // Patient created successfully, now book appointment with the created patient's name
                        const appointmentDataWithPatient = {
                            ...appointmentData.value,
                            patient_name: patientData.value.name
                        };
                        
                        // Parse the date for appointment booking
                        const parsedDate = parseNaturalDate(appointmentDataWithPatient.date || '');
                        if (!parsedDate) {
                            throw new Error('Invalid date. Please provide a valid future date.');
                        }
                        
                        const appointmentResponse = await window.axios.post('/api/chat/appointment', {
                            patient_name: appointmentDataWithPatient.patient_name,
                            date: parsedDate,
                            time: appointmentDataWithPatient.time,
                            type: appointmentDataWithPatient.type,
                            reason: appointmentDataWithPatient.reason || ''
                        });
                        
                        if (appointmentResponse.data.success) {
                            messages.value.push({ 
                                role: 'assistant', 
                                content: `Patient created successfully and ${appointmentResponse.data.message}`,
                                type: 'success'
                            });
                        } else {
                            messages.value.push({ 
                                role: 'assistant', 
                                content: `Patient created successfully, but ${appointmentResponse.data.message || 'there was an error booking the appointment.'}`,
                                type: 'error'
                            });
                        }
                    } else {
                        messages.value.push({ 
                            role: 'assistant', 
                            content: patientResponse.data.message || 'There was an error creating the patient record.',
                            type: 'error'
                        });
                    }
                } catch (error) {
                    console.error('Patient creation/appointment booking error:', error);
                    messages.value.push({ 
                        role: 'assistant', 
                        content: 'I encountered an error while creating the patient record and booking the appointment. Please try again.',
                        type: 'error'
                    });
                }
            }
        } else if (currentForm.value === 'appointment') {
            // Special handling for appointment type selection
            if (currentField.value === 'type') {
                // Find matching type
                const matchedType = appointmentTypes.find(
                    t => t.value.toLowerCase() === userInput.toLowerCase() || 
                         t.label.toLowerCase().includes(userInput.toLowerCase())
                );
                
                if (!matchedType) {
                    messages.value.push({
                        role: 'assistant',
                        content: 'Invalid appointment type. Please choose from the options provided.',
                        isError: true
                    });
                    await collectAppointmentInfo('type');
                    return;
                }
                appointmentData.value.type = matchedType.value;
            } else {
                // Handle other appointment fields
                if (currentField.value === 'date') {
                    // Parse the date to convert natural language to actual date
                    const parsedDate = parseNaturalDate(userInput);
                    if (parsedDate) {
                        appointmentData.value[currentField.value] = parsedDate;
                    } else {
                        appointmentData.value[currentField.value] = userInput; // Fallback to original input
                    }
                } else {
                    appointmentData.value[currentField.value] = userInput;
                }
            }
            
            // Determine next field or show confirmation
            const fields = ['patient_name', 'date', 'time', 'type', 'notes'];
            const currentIndex = fields.indexOf(currentField.value);
            
            if (currentIndex < fields.length - 1) {
                // Move to next field
                await collectAppointmentInfo(fields[currentIndex + 1]);
            } else {
                // All fields collected, show confirmation
                collectingInfo.value = false;
                currentField.value = '';
                
                // Format appointment info for display
                const appointmentType = appointmentTypes.find(t => t.value === appointmentData.value.type)?.label || appointmentData.value.type;
                const appointmentInfo = `Please confirm the appointment details:\n\n` +
                    `Patient Name: ${appointmentData.value.patient_name}\n` +
                    `Date: ${appointmentData.value.date}\n` +
                    `Time: ${appointmentData.value.time}\n` +
                    `Type: ${appointmentType}` +
                    (appointmentData.value.notes ? `\nNotes: ${appointmentData.value.notes}` : '');
                
                const messageIndex = messages.value.push({
                    role: 'assistant',
                    content: appointmentInfo,
                    structured: true,
                    data: {
                        action: 'book_appointment',
                        data: { 
                            ...appointmentData.value,
                            // Format date for API if needed
                            appointment_date: appointmentData.value.date,
                            appointment_time: appointmentData.value.time
                        },
                        message: appointmentInfo
                    },
                    showButtons: true
                }) - 1;
                
                // Set pending action
                pendingAction.value = {
                    action: 'book_appointment',
                    data: { 
                        ...appointmentData.value,
                        appointment_date: appointmentData.value.date,
                        appointment_time: appointmentData.value.time
                    },
                    messageIndex
                };
            }
        }
    } catch (error) {
        console.error('Error processing user input:', error);
        messages.value.push({
            role: 'assistant',
            content: 'Sorry, I encountered an error while processing your input. Please try again.',
            isError: true
        });
        collectingInfo.value = false;
        currentField.value = '';
    }
};

const sendMessage = async () => {
    if (!newMessage.value.trim() || isLoading.value) return;

    const userMsg = newMessage.value.trim();
    messages.value.push({ role: 'user', content: userMsg });
    newMessage.value = '';
    isLoading.value = true;
    scrollToBottom();

    try {
        // If we're collecting info, process the input directly
        if (collectingInfo.value) {
            await processUserInput(userMsg);
            isLoading.value = false;
            return;
        }
        
        // Otherwise, process as a normal message
        const response = await window.axios.post('/api/chat', {
            message: userMsg,
            history: messages.value.slice(0, -1).map(msg => ({
                role: msg.role,
                content: msg.content
            }))
        });

        if (response.data.structured && response.data.data) {
            const messageData = response.data.data;
            
            if (messageData.action) {
                // Handle different form types
                if (messageData.action === 'create_patient') {
                    collectingInfo.value = true;
                    currentForm.value = 'patient';
                    await collectPatientInfo('name');
                } else if (messageData.action === 'book_appointment') {
                    collectingInfo.value = true;
                    currentForm.value = 'appointment';
                    await collectAppointmentInfo('patient_name');
                } else {
                    // Show confirmation for other structured messages
                    const messageObj = { 
                        role: 'assistant', 
                        content: messageData.message,
                        structured: true,
                        data: messageData,
                        showButtons: true
                    };
                    
                    const messageIndex = messages.value.push(messageObj) - 1;
                    
                    if (messageData.action) {
                        pendingAction.value = { 
                            ...messageData,
                            messageIndex
                        };
                        messages.value = [...messages.value];
                    }
                }
            }
        } else {
            // Regular text response
            const messageObj = {
                role: 'assistant',
                content: response.data.response,
                structured: false,
                showButtons: false
            };
            
            messages.value.push(messageObj);
            
            // Check if response contains raw JSON that wasn't properly parsed as structured
            if (!response.data.structured && response.data.response) {
                // Try to parse raw response as JSON
                try {
                    const rawJsonMatch = response.data.response.match(/\{.*\}/);
                    if (rawJsonMatch) {
                        const parsedJson = JSON.parse(rawJsonMatch[0]);
                        if (parsedJson.action === 'create_patient') {
                            collectingInfo.value = true;
                            currentForm.value = 'patient';
                            await collectPatientInfo('name');
                        } else if (parsedJson.action === 'book_appointment') {
                            collectingInfo.value = true;
                            currentForm.value = 'appointment';
                            await collectAppointmentInfo('patient_name');
                        }
                        return; // Skip adding the raw response as a message
                    }
                } catch (e) {
                    console.log('Failed to parse raw JSON:', e);
                    
                    // Show more helpful error message for raw JSON
                    messages.value.push({
                        role: 'assistant',
                        content: "I'm having trouble processing structured requests right now. Let me help you step by step. What would you like to do?",
                        structured: false,
                        showButtons: false
                    });
                }
            }
        }
    } catch (error) {
        console.error('Chat error:', error);
        messages.value.push({ role: 'assistant', content: "I'm sorry, I'm having trouble connecting right now." });
    } finally {
        isLoading.value = false;
        scrollToBottom();
    }
};

watch(messages, () => {
    scrollToBottom();
}, { deep: true });
</script>

<template>
    <div class="fixed bottom-6 right-6 z-50 font-sans">
        <!-- Chat Window -->
        <div v-if="isOpen" 
             class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-80 sm:w-96 flex flex-col mb-4 border border-gray-200 dark:border-gray-800 overflow-hidden transition-all duration-300 ease-in-out transform origin-bottom-right"
             style="height: 500px; max-height: 80vh;">
            
            <!-- Header -->
            <div class="bg-blue-600 dark:bg-slate-900 p-4 flex justify-between items-center text-white">
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                    <h3 class="font-semibold">Dental Assistant</h3>
                </div>
                <button @click="toggleChat" class="hover:bg-blue-700 dark:hover:bg-slate-800 p-1 rounded transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <!-- Messages Area -->
            <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50 dark:bg-slate-900/50">
                <div v-for="(msg, index) in messages" :key="index" 
                     :class="['flex', msg.role === 'user' ? 'justify-end' : 'justify-start']">
                    <div :class="[
                        'max-w-[80%] rounded-2xl px-4 py-2 text-sm shadow-sm',
                        msg.role === 'user' 
                            ? 'bg-blue-600 text-white rounded-br-none' 
                            : msg.type === 'success'
                            ? 'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200 border border-green-200 dark:border-green-800 rounded-bl-none'
                            : msg.type === 'error'
                            ? 'bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-200 border border-red-200 dark:border-red-800 rounded-bl-none'
                            : 'bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-100 border border-gray-100 dark:border-gray-700 rounded-bl-none'
                    ]">
                        {{ msg.content }}
                        
                        <!-- Simple Confirmation Buttons -->
                        <div v-if="msg.showButtons" class="mt-3 flex space-x-2">
                            <button @click="confirmAction" 
                                    class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                ✓ Confirm
                            </button>
                            <button @click="cancelAction" 
                                    class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 text-sm rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Typing Indicator -->
                <div v-if="isLoading" class="flex justify-start">
                    <div class="bg-white dark:bg-slate-800 border border-gray-100 dark:border-gray-700 rounded-2xl rounded-bl-none px-4 py-3 shadow-sm flex space-x-1">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                    </div>
                </div>
            </div>

            <!-- Input Area -->
            <div class="p-4 bg-white dark:bg-slate-800 border-t border-gray-100 dark:border-gray-700">
                <form @submit.prevent="sendMessage" class="flex space-x-2">
                    <input 
                        v-model="newMessage" 
                        type="text" 
                        placeholder="Type your question..." 
                        class="flex-1 border border-gray-300 dark:border-gray-600 rounded-full px-4 py-2 text-sm focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-500 dark:focus:ring-blue-400 transition-all bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                        :disabled="isLoading"
                    >
                    <button 
                        type="submit" 
                        :disabled="!newMessage.trim() || isLoading"
                        class="bg-blue-600 dark:bg-blue-700 text-white rounded-full p-2 hover:bg-blue-700 dark:hover:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        <!-- Toggle Button -->
        <button 
            @click="toggleChat"
            class="bg-blue-600 dark:bg-blue-700 hover:bg-blue-700 dark:hover:bg-blue-800 text-white rounded-full p-4 shadow-lg transition-all duration-300 transform hover:scale-110 flex items-center justify-center"
            :class="{ 'rotate-90 opacity-0 pointer-events-none absolute': isOpen }"
        >
            <!-- Chat Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
        </button>
    </div>
</template>
