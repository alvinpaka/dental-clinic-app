<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateDentalMedicinesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dental_medicines', function (Blueprint $table) {
            $table->id('medicine_id');
            $table->string('medicine_name', 100);
            $table->string('category', 50);
            $table->string('dosage_form', 50)->nullable();
            $table->text('common_uses')->nullable();
            $table->boolean('prescription_required')->default(true);
            $table->timestamps();
        });

        // Insert Local Anesthetics
        DB::table('dental_medicines')->insert([
            [
                'medicine_name' => 'Lidocaine 2% with Epinephrine 1:100,000',
                'category' => 'Local Anesthetic',
                'dosage_form' => 'Injection',
                'common_uses' => 'Local anesthesia for dental procedures',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Articaine 4% with Epinephrine 1:100,000',
                'category' => 'Local Anesthetic',
                'dosage_form' => 'Injection',
                'common_uses' => 'Local anesthesia, especially for mandibular blocks',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Mepivacaine 3%',
                'category' => 'Local Anesthetic',
                'dosage_form' => 'Injection',
                'common_uses' => 'Local anesthesia for patients avoiding vasoconstrictors',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Bupivacaine 0.5% with Epinephrine',
                'category' => 'Local Anesthetic',
                'dosage_form' => 'Injection',
                'common_uses' => 'Long-acting local anesthesia',
                'prescription_required' => true,
            ],
        ]);

        // Insert Analgesics (Pain Relievers)
        DB::table('dental_medicines')->insert([
            [
                'medicine_name' => 'Ibuprofen 400mg',
                'category' => 'Analgesic',
                'dosage_form' => 'Tablet',
                'common_uses' => 'Pain relief and inflammation reduction',
                'prescription_required' => false,
            ],
            [
                'medicine_name' => 'Acetaminophen 500mg',
                'category' => 'Analgesic',
                'dosage_form' => 'Tablet',
                'common_uses' => 'Pain relief without anti-inflammatory effects',
                'prescription_required' => false,
            ],
            [
                'medicine_name' => 'Naproxen 500mg',
                'category' => 'Analgesic',
                'dosage_form' => 'Tablet',
                'common_uses' => 'Pain relief and anti-inflammatory',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Tramadol 50mg',
                'category' => 'Analgesic',
                'dosage_form' => 'Tablet',
                'common_uses' => 'Moderate to severe pain',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Codeine with Acetaminophen',
                'category' => 'Analgesic',
                'dosage_form' => 'Tablet',
                'common_uses' => 'Moderate pain relief',
                'prescription_required' => true,
            ],
        ]);

        // Insert Antibiotics
        DB::table('dental_medicines')->insert([
            [
                'medicine_name' => 'Amoxicillin 500mg',
                'category' => 'Antibiotic',
                'dosage_form' => 'Capsule',
                'common_uses' => 'Dental infections, prophylaxis for endocarditis',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Clindamycin 300mg',
                'category' => 'Antibiotic',
                'dosage_form' => 'Capsule',
                'common_uses' => 'Penicillin-allergic patients, dental infections',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Azithromycin 500mg',
                'category' => 'Antibiotic',
                'dosage_form' => 'Tablet',
                'common_uses' => 'Dental infections, penicillin allergy',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Metronidazole 500mg',
                'category' => 'Antibiotic',
                'dosage_form' => 'Tablet',
                'common_uses' => 'Anaerobic infections, periodontal disease',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Cephalexin 500mg',
                'category' => 'Antibiotic',
                'dosage_form' => 'Capsule',
                'common_uses' => 'Dental infections',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Doxycycline 100mg',
                'category' => 'Antibiotic',
                'dosage_form' => 'Tablet',
                'common_uses' => 'Periodontal disease, actinomycosis',
                'prescription_required' => true,
            ],
        ]);

        // Insert Antimicrobial Mouth Rinses
        DB::table('dental_medicines')->insert([
            [
                'medicine_name' => 'Chlorhexidine Gluconate 0.12%',
                'category' => 'Antimicrobial Rinse',
                'dosage_form' => 'Mouthwash',
                'common_uses' => 'Gingivitis, periodontal therapy, post-surgery',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Povidone-Iodine 10%',
                'category' => 'Antimicrobial Rinse',
                'dosage_form' => 'Solution',
                'common_uses' => 'Pre-procedural rinse, disinfection',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Hydrogen Peroxide 3%',
                'category' => 'Antimicrobial Rinse',
                'dosage_form' => 'Solution',
                'common_uses' => 'Oral irrigation, mild antiseptic',
                'prescription_required' => false,
            ],
        ]);

        // Insert Corticosteroids
        DB::table('dental_medicines')->insert([
            [
                'medicine_name' => 'Dexamethasone 4mg',
                'category' => 'Corticosteroid',
                'dosage_form' => 'Tablet',
                'common_uses' => 'Reducing inflammation and swelling',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Prednisone 10mg',
                'category' => 'Corticosteroid',
                'dosage_form' => 'Tablet',
                'common_uses' => 'Anti-inflammatory for severe swelling',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Triamcinolone Acetonide 0.1%',
                'category' => 'Corticosteroid',
                'dosage_form' => 'Paste',
                'common_uses' => 'Oral lesions, canker sores',
                'prescription_required' => true,
            ],
        ]);

        // Insert Antifungal Medications
        DB::table('dental_medicines')->insert([
            [
                'medicine_name' => 'Nystatin 100,000 units/ml',
                'category' => 'Antifungal',
                'dosage_form' => 'Suspension',
                'common_uses' => 'Oral candidiasis (thrush)',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Clotrimazole 10mg',
                'category' => 'Antifungal',
                'dosage_form' => 'Troche',
                'common_uses' => 'Oral candidiasis',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Fluconazole 100mg',
                'category' => 'Antifungal',
                'dosage_form' => 'Tablet',
                'common_uses' => 'Systemic fungal infections',
                'prescription_required' => true,
            ],
        ]);

        // Insert Emergency Medications
        DB::table('dental_medicines')->insert([
            [
                'medicine_name' => 'Epinephrine 1:1000',
                'category' => 'Emergency',
                'dosage_form' => 'Injection',
                'common_uses' => 'Anaphylaxis, cardiac arrest',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Diphenhydramine 50mg',
                'category' => 'Emergency',
                'dosage_form' => 'Injection',
                'common_uses' => 'Allergic reactions',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Albuterol Inhaler',
                'category' => 'Emergency',
                'dosage_form' => 'Inhaler',
                'common_uses' => 'Asthma attack, bronchospasm',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Nitroglycerin 0.4mg',
                'category' => 'Emergency',
                'dosage_form' => 'Sublingual Tablet',
                'common_uses' => 'Angina pectoris',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Aspirin 325mg',
                'category' => 'Emergency',
                'dosage_form' => 'Tablet',
                'common_uses' => 'Suspected myocardial infarction',
                'prescription_required' => false,
            ],
            [
                'medicine_name' => 'Glucagon 1mg',
                'category' => 'Emergency',
                'dosage_form' => 'Injection',
                'common_uses' => 'Severe hypoglycemia',
                'prescription_required' => true,
            ],
        ]);

        // Insert Sedatives and Anxiolytics
        DB::table('dental_medicines')->insert([
            [
                'medicine_name' => 'Diazepam 5mg',
                'category' => 'Sedative',
                'dosage_form' => 'Tablet',
                'common_uses' => 'Pre-operative anxiety, muscle relaxation',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Lorazepam 1mg',
                'category' => 'Sedative',
                'dosage_form' => 'Tablet',
                'common_uses' => 'Anxiety relief before procedures',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Midazolam 1mg/ml',
                'category' => 'Sedative',
                'dosage_form' => 'Injection',
                'common_uses' => 'Conscious sedation',
                'prescription_required' => true,
            ],
        ]);

        // Insert Topical Applications
        DB::table('dental_medicines')->insert([
            [
                'medicine_name' => 'Benzocaine 20%',
                'category' => 'Topical Anesthetic',
                'dosage_form' => 'Gel',
                'common_uses' => 'Topical anesthesia before injections',
                'prescription_required' => false,
            ],
            [
                'medicine_name' => 'Lidocaine 5%',
                'category' => 'Topical Anesthetic',
                'dosage_form' => 'Ointment',
                'common_uses' => 'Topical anesthesia for mucous membranes',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Fluoride 1.23%',
                'category' => 'Preventive',
                'dosage_form' => 'Gel',
                'common_uses' => 'Caries prevention, desensitization',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Calcium Hydroxide',
                'category' => 'Endodontic',
                'dosage_form' => 'Paste',
                'common_uses' => 'Direct and indirect pulp capping',
                'prescription_required' => true,
            ],
        ]);

        // Insert Hemostatic Agents
        DB::table('dental_medicines')->insert([
            [
                'medicine_name' => 'Absorbable Gelatin Sponge',
                'category' => 'Hemostatic',
                'dosage_form' => 'Sponge',
                'common_uses' => 'Control bleeding in extraction sockets',
                'prescription_required' => false,
            ],
            [
                'medicine_name' => 'Tranexamic Acid 4.8%',
                'category' => 'Hemostatic',
                'dosage_form' => 'Mouthwash',
                'common_uses' => 'Control bleeding in anticoagulated patients',
                'prescription_required' => true,
            ],
            [
                'medicine_name' => 'Collagen-based Hemostatic',
                'category' => 'Hemostatic',
                'dosage_form' => 'Pad',
                'common_uses' => 'Surgical bleeding control',
                'prescription_required' => false,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dental_medicines');
    }
}