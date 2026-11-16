<?

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\AccountSubCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $cashAccountCode = $this->generateUniqueCode('CASH');
        Account::create([
            'name' => 'حساب صندوق',
            'code' => $cashAccountCode, // Use the unique code here
            'contact' => '0',
            'address' => 'کابل، افغانستان',
            'company' => null,
            'account_sub_category_id' => AccountSubCategory::where('name', 'Safe')->first()->id,
            'is_active' => true,
            'created_by' => 1,
            'is_safe' => true,
            'bi_icon' => 'bi-cash',
            'bi_icon_color' => 'success',
            'profile_bg' => '#ffffff',
        ]);

        $profitLossAccountCode = $this->generateUniqueCode('PL');
        Account::create([
            'name' => 'مفاد و ضرر',
            'code' => $profitLossAccountCode, // Use the unique code here
            'contact' => '0',
            'address' => 'کابل، افغانستان',
            'company' => null,
            'account_sub_category_id' => AccountSubCategory::where('name', 'PnL')->first()->id,
            'is_active' => true,
            'created_by' => 1,
            'is_safe' => true,
            'bi_icon' => 'bi-cash',
            'bi_icon_color' => 'success',
            'profile_bg' => '#ffffff',
        ]);

        Account::create([
            'name' => 'مصارف',
            'code' => 'EXP100', // Use the unique code here
            'contact' => '0',
            'address' => 'کابل، افغانستان',
            'company' => null,
            'account_sub_category_id' => AccountSubCategory::where('name', 'EXP')->first()->id,
            'is_active' => true,
            'created_by' => 1,
            'is_safe' => true,
            'bi_icon' => 'bi-cash',
            'bi_icon_color' => 'success',
            'profile_bg' => '#ffffff',
        ]);

        Account::create([
            'name' => 'Unpaid',
            'code' => $this->generateUniqueCode('UPD'), // Use the unique code here
            'contact' => '0',
            'address' => 'کابل، افغانستان',
            'company' => null,
            'account_sub_category_id' => AccountSubCategory::where('name', 'Safe')->first()->id,
            'is_active' => true,
            'created_by' => 1,
            'is_safe' => true,
            'bi_icon' => 'bi-cash',
            'bi_icon_color' => 'success',
            'profile_bg' => '#ffffff',
        ]);

        
    }

    /**
     * Generate a unique code for the given prefix.
     *
     * @param string $prefix
     * @return string
     */
    private function generateUniqueCode($prefix)
    {
        // Find the highest code with the given prefix
        $maxCode = Account::where('code', 'LIKE', $prefix . '%')
            ->orderBy('code', 'desc')
            ->pluck('code')
            ->first();

        // Extract the numerical part of the code and increment it
        if ($maxCode) {
            $numericPart = (int) str_replace($prefix, '', $maxCode);
            $newNumericPart = $numericPart + 1;
            $newCode = $prefix . $newNumericPart;
        } else {
            // If no such code exists, start with the first number (100)
            $newCode = $prefix . '100';
        }

        return $newCode;
    }
}
