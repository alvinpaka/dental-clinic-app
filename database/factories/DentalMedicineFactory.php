<?php

namespace Database\Factories;

use App\Models\DentalMedicine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DentalMedicine>
 */
class DentalMedicineFactory extends Factory
{
    protected $model = DentalMedicine::class;

    public function definition()
    {
        $medicines = [
            ['name' => 'Amoxicillin', 'description' => 'Broad-spectrum antibiotic', 'category' => 'antibiotic'],
            ['name' => 'Ibuprofen', 'description' => 'NSAID for pain relief', 'category' => 'analgesic'],
            ['name' => 'Penicillin VK', 'description' => 'Antibiotic for bacterial infections', 'category' => 'antibiotic'],
            ['name' => 'Clindamycin', 'description' => 'Antibiotic for serious infections', 'category' => 'antibiotic'],
            ['name' => 'Azithromycin', 'description' => 'Macrolide antibiotic', 'category' => 'antibiotic'],
            ['name' => 'Acetaminophen', 'description' => 'Pain reliever and fever reducer', 'category' => 'analgesic'],
            ['name' => 'Lidocaine', 'description' => 'Local anesthetic', 'category' => 'anesthetic'],
            ['name' => 'Chlorhexidine', 'description' => 'Antiseptic mouthwash', 'category' => 'antiseptic'],
            ['name' => 'Fluoride', 'description' => 'Prevents tooth decay', 'category' => 'preventive'],
            ['name' => 'Metronidazole', 'description' => 'Antibiotic for anaerobic infections', 'category' => 'antibiotic'],
            ['name' => 'Doxycycline', 'description' => 'Tetracycline antibiotic', 'category' => 'antibiotic'],
            ['name' => 'Codeine', 'description' => 'Opioid pain medication', 'category' => 'analgesic'],
            ['name' => 'Hydrocodone', 'description' => 'Strong pain medication', 'category' => 'analgesic'],
            ['name' => 'Prednisone', 'description' => 'Corticosteroid', 'category' => 'steroid'],
            ['name' => 'Triamcinolone', 'description' => 'Topical corticosteroid', 'category' => 'steroid'],
        ];

        $medicine = $this->faker->randomElement($medicines);

        return [
            'name' => $medicine['name'],
            'description' => $medicine['description'],
            'category' => $medicine['category'],
            'dosage_form' => $this->faker->randomElement(['tablet', 'capsule', 'liquid', 'gel', 'ointment', 'mouthwash']),
            'strength' => $this->faker->randomElement(['250mg', '500mg', '100mg', '200mg', '50mg', '0.5%', '1%', '2%']),
            'manufacturer' => $this->faker->company(),
            'is_active' => true,
            'requires_prescription' => $this->faker->boolean(80), // 80% require prescription
            'stock_quantity' => $this->faker->numberBetween(0, 1000),
            'min_stock_level' => $this->faker->numberBetween(10, 50),
            'unit_price' => $this->faker->numberBetween(500, 50000), // in cents
            'expiry_date' => $this->faker->dateTimeBetween('+1 year', '+5 years'),
        ];
    }

    public function antibiotic()
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'antibiotic',
            'requires_prescription' => true,
        ]);
    }

    public function analgesic()
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'analgesic',
            'requires_prescription' => $this->faker->boolean(60),
        ]);
    }

    public function anesthetic()
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'anesthetic',
            'requires_prescription' => true,
        ]);
    }

    public function antiseptic()
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'antiseptic',
            'requires_prescription' => false,
        ]);
    }

    public function preventive()
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'preventive',
            'requires_prescription' => false,
        ]);
    }

    public function steroid()
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'steroid',
            'requires_prescription' => true,
        ]);
    }

    public function inactive()
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function lowStock()
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => $this->faker->numberBetween(0, 10),
        ]);
    }

    public function outOfStock()
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
        ]);
    }

    public function requiresPrescription()
    {
        return $this->state(fn (array $attributes) => [
            'requires_prescription' => true,
        ]);
    }

    public function overTheCounter()
    {
        return $this->state(fn (array $attributes) => [
            'requires_prescription' => false,
        ]);
    }

    public function tablet()
    {
        return $this->state(fn (array $attributes) => [
            'dosage_form' => 'tablet',
        ]);
    }

    public function capsule()
    {
        return $this->state(fn (array $attributes) => [
            'dosage_form' => 'capsule',
        ]);
    }

    public function liquid()
    {
        return $this->state(fn (array $attributes) => [
            'dosage_form' => 'liquid',
        ]);
    }

    public function gel()
    {
        return $this->state(fn (array $attributes) => [
            'dosage_form' => 'gel',
        ]);
    }

    public function mouthwash()
    {
        return $this->state(fn (array $attributes) => [
            'dosage_form' => 'mouthwash',
        ]);
    }

    public function expensive()
    {
        return $this->state(fn (array $attributes) => [
            'unit_price' => $this->faker->numberBetween(25000, 100000), // $250-$1000
        ]);
    }

    public function affordable()
    {
        return $this->state(fn (array $attributes) => [
            'unit_price' => $this->faker->numberBetween(500, 5000), // $5-$50
        ]);
    }

    public function expiringSoon()
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => $this->faker->dateTimeBetween('now', '+3 months'),
        ]);
    }

    public function longExpiry()
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => $this->faker->dateTimeBetween('+3 years', '+10 years'),
        ]);
    }
}
