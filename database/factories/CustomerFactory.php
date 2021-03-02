<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'email' => $this->faker->unique()->safeEmail,
            'title' => $this->faker->title,
            'given_name' => $this->faker->firstName,
            'family_name' => $this->faker->lastName,
            'company_name' => $this->faker->company,
            'phone' => $this->faker->phoneNumber,
            'dnd' => $this->faker->boolean,
            'customer_type' => $this->faker->randomElement(['lead', 'customer']),
            'archived'=> false,
            'ein' => $this->faker->randomNumber(8),
            'website' => $this->faker->url,
            'company_number'=>$this->faker->phoneNumber,
            'source' => $this->faker->randomElement([  'Adwords Wirelesshomealarms.com.au',
            'SEO Element Website',
            'Telemarketing Campaign',
            'Word of Mouth',
            'Trade Show',
            'Past Customer',
            'HIA Home Show 2017 Form',
            'HIA Home Show 2017 Scanner',
            'The Drop',
            'Cold Call',
            'Alarm.com referral',
            'Response to Email Marketing']),
            'is_company' => $this->faker->boolean,
            'have_subscription' => $this->faker->boolean,
        ];
    }

    /* public function configure()
    {
        return $this->afterCreating(function (Customer $customer){

            $customer->hasOne(Address::class, 'customer')->create();
            $customer->hasOne(Address::class, 'customer')->create(['customer'=> $customer->id, 'type'=> 'billing']);
        });
    } */
}
