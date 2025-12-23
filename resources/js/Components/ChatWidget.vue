<script setup>
import { ref, nextTick, watch } from 'vue';
import axios from 'axios';

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

const executeAction = async (actionData) => {
    try {
        let endpoint = '';
        let data = {};

        if (actionData.action === 'create_patient') {
            endpoint = '/api/chat/patient';
            data = {
                name: actionData.data.name,
                email: actionData.data.email,
                phone: actionData.data.phone,
                dob: actionData.data.dob,
                age: actionData.data.age,
                address: actionData.data.address,
            };
        } else if (actionData.action === 'book_appointment') {
            endpoint = '/api/chat/appointment';
            data = {
                patient_name: actionData.data.patient_name,
                date: actionData.data.date,
                time: actionData.data.time,
                type: actionData.data.type,
                notes: actionData.data.notes,
            };
        }

        const response = await axios.post(endpoint, data);
        
        if (response.data.success) {
            messages.value.push({ 
                role: 'assistant', 
                content: response.data.message,
                type: 'success'
            });
        } else {
            let errorMessage = response.data.message;
            
            // If patient not found, suggest creating patient
            if (response.status === 404 && response.data.action === 'create_patient') {
                errorMessage += ' Would you like me to help you create a patient record first?';
            }
            
            messages.value.push({ 
                role: 'assistant', 
                content: errorMessage,
                type: 'error'
            });
        }

        pendingAction.value = null;
        scrollToBottom();

    } catch (error) {
        console.error('Action execution error:', error);
        messages.value.push({ 
            role: 'assistant', 
            content: 'I encountered an error while processing your request. Please try again.',
            type: 'error'
        });
        pendingAction.value = null;
        scrollToBottom();
    }
};

const confirmAction = () => {
    if (pendingAction.value) {
        executeAction(pendingAction.value);
    }
};

const cancelAction = () => {
    pendingAction.value = null;
    messages.value.push({ 
        role: 'assistant', 
        content: 'No problem! Is there anything else I can help you with?' 
    });
    scrollToBottom();
};

const sendMessage = async () => {
    if (!newMessage.value.trim() || isLoading.value) return;

    const userMsg = newMessage.value.trim();
    messages.value.push({ role: 'user', content: userMsg });
    newMessage.value = '';
    isLoading.value = true;
    pendingAction.value = null;
    scrollToBottom();

    try {
        const response = await axios.post('/api/chat', {
            message: userMsg,
            history: messages.value.slice(0, -1).map(msg => ({
                role: msg.role,
                content: msg.content
            }))
        });

        if (response.data.structured && response.data.data) {
            // Handle structured response
            const messageData = response.data.data;
            
            messages.value.push({ 
                role: 'assistant', 
                content: messageData.message,
                structured: true,
                data: messageData
            });

            // Set pending action for confirmation
            if (messageData.action === 'create_patient' || messageData.action === 'book_appointment') {
                pendingAction.value = messageData;
            }
        } else {
            // Regular text response
            messages.value.push({ role: 'assistant', content: response.data.response });
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
                        
                        <!-- Confirmation Buttons for Structured Responses -->
                        <div v-if="msg.structured && pendingAction && msg.data.action === pendingAction.action" 
                             class="mt-3 space-y-2">
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-2">Would you like me to proceed?</div>
                            <div class="flex space-x-2">
                                <button @click="confirmAction" 
                                        class="bg-blue-600 dark:bg-blue-700 text-white px-3 py-1 rounded-full text-xs hover:bg-blue-700 dark:hover:bg-blue-800 transition-colors">
                                    ✓ Confirm
                                </button>
                                <button @click="cancelAction" 
                                        class="bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 px-3 py-1 rounded-full text-xs hover:bg-gray-400 dark:hover:bg-gray-500 transition-colors">
                                    Cancel
                                </button>
                            </div>
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
