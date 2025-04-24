<?php

namespace Database\Factories;

use App\Models\HousePayment;
use App\Models\House;
use App\Models\WebUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage; // Necesario para Opción B

class HousePaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = HousePayment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // --- Opción A: URL de Placeholder (Recomendado) ---
        $imagePath = $this->faker->imageUrl(640, 480, 'house', true, 'payment'); // Genera una URL a una imagen aleatoria

        // --- Opción B: Archivos Locales (Si tienes imágenes en storage/app/public/payments) ---
        /*
        // 1. Asegúrate de que el directorio exista y tenga imágenes
        $directory = 'public/payments';
        if (!Storage::exists($directory)) {
             Storage::makeDirectory($directory);
             // Podrías copiar algunas imágenes aquí programáticamente si quisieras,
             // pero es más fácil ponerlas manualmente para seeding.
             // Ejemplo: file_put_contents(storage_path('app/public/payments/placeholder1.jpg'), file_get_contents('https://via.placeholder.com/640x480.png?text=Payment+1'));
        }

        // 2. Obtén la lista de archivos (¡ASEGÚRATE que solo sean imágenes!)
        $allFiles = Storage::files($directory); // Obtiene ['public/payments/img1.jpg', 'public/payments/img2.png']

        // 3. Selecciona uno aleatorio (o usa un placeholder si no hay archivos)
        if (!empty($allFiles)) {
            // Quitamos 'public/' del path para guardarlo relativo al enlace simbólico 'storage'
            $imagePath = str_replace('public/', '', $this->faker->randomElement($allFiles));
        } else {
            // Fallback si no hay imágenes locales
             $imagePath = 'payments/default_payment.png'; // O genera una URL placeholder como en Opción A
            // $imagePath = $this->faker->imageUrl(640, 480, 'house', true, 'payment');
        }
        */
        // --- Fin Opción B ---
        $paymentTitles = [
            'Pago de luz (Áreas Comunes)',
            'Pago de agua (Áreas Comunes)',
            'Cuota de Mantenimiento Regular',
            'Gastos Comunes Ordinarios',
            'Fondo de Reserva',
            'Pago de gas (si aplica)',
            'Seguro del Edificio',
            'Servicio de Seguridad/Vigilancia',
            'Limpieza de Áreas Comunes',
            'Mantenimiento de Ascensor',
            'Mantenimiento de Jardines/Paisajismo',
            'Mantenimiento de Piscina',
            'Reparaciones Generales Menores',
            'Control de Plagas',
            'Administración del Condominio',
            'Recolección de Basura',
            'Mantenimiento Bomba de Agua',
            'Derrama Extraordinaria (Pintura Fachada)',
            'Derrama Extraordinaria (Impermeabilización Techo)',
            'Mantenimiento Portón Eléctrico',
            'Internet/WiFi Comunitario (si aplica)',
            'Salarios Personal (Conserje/Administrador)',
        ];
        $randomTitle = $this->faker->randomElement($paymentTitles);

        return [
            'web_user_id' => WebUser::inRandomOrder()->first()->id,
            'house_id' => House::inRandomOrder()->first()->id,
            'amount' => $this->faker->randomFloat(2, 50, 1500),
            'payment_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'title' => $randomTitle,
            'description' => $randomTitle,
            'file_name' => $this->faker->title,
            'file_path' => $imagePath,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
