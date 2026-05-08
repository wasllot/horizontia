<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        Language::truncate();
        $languages = [
            [
                'name' => "Afrikaans",
                'status' => '1'
            ],
            [
                'name' => "Albanian",
                'status' => '1'
            ],
            [
                'name' => "Amharic",
                'status' => '1'
            ],
            [
                'name' => "Arabic",
                'status' => '1'
            ],
            [
                'name' => "Aragonese",
                'status' => '1'
            ],
            [
                'name' => "Armenian",
                'status' => '1'
            ],
            [
                'name' => "Asturian",
                'status' => '1'
            ],
            [
                'name' => "Azerbaijani",
                'status' => '1'
            ],
            [
                'name' => "Basque",
                'status' => '1'
            ],
            [
                'name' => "Belarusian",
                'status' => '1'
            ],
            [
                'name' => "Bengali",
                'status' => '1'
            ],
            [
                'name' => "Bosnian",
                'status' => '1'
            ],
            [
                'name' => "Breton",
                'status' => '1'
            ],
            [
                'name' => "Bulgarian",
                'status' => '1'
            ],
            [
                'name' => "Catalan",
                'status' => '1'
            ],
            [
                'name' => "Central Kurdish",
                'status' => '1'
            ],
            [
                'name' => "Chinese",
                'status' => '1'
            ],
            [
                'name' => "Corsican",
                'status' => '1'
            ],
            [
                'name' => "Croatian",
                'status' => '1'
            ],
            [
                'name' => "Czech",
                'status' => '1'
            ],
            [
                'name' => "Danish",
                'status' => '1'
            ],
            [
                'name' => "Dutch",
                'status' => '1'
            ],
            [
                'name' => "English",
                'status' => '1'
            ],
            [
                'name' => "Esperanto",
                'status' => '1'
            ],
            [
                'name' => "Estonian",
                'status' => '1'
            ],
            [
                'name' => "Faroese",
                'status' => '1'
            ],
            [
                'name' => "Filipino",
                'status' => '1'
            ],
            [
                'name' => "Finnish",
                'status' => '1'
            ],
            [
                'name' => "French",
                'status' => '1'
            ],
            [
                'name' => "French",
                'status' => '1'
            ],
            [
                'name' => "Galician",
                'status' => '1'
            ],
            [
                'name' => "Georgian",
                'status' => '1'
            ],
            [
                'name' => "German",
                'status' => '1'
            ],
            [
                'name' => "German",
                'status' => '1'
            ],
            [
                'name' => "Greek",
                'status' => '1'
            ],
            [
                'name' => "Guarani",
                'status' => '1'
            ],
            [
                'name' => "Gujarati",
                'status' => '1'
            ],
            [
                'name' => "Hausa",
                'status' => '1'
            ],
            [
                'name' => "Hawaiian",
                'status' => '1'
            ],
            [
                'name' => "Hebrew",
                'status' => '1'
            ],
            [
                'name' => "Hindi",
                'status' => '1'
            ],
            [
                'name' => "Hungarian",
                'status' => '1'
            ],
            [
                'name' => "Icelandic",
                'status' => '1'
            ],
            [
                'name' => "Indonesian",
                'status' => '1'
            ],
            [
                'name' => "Interlingua",
                'status' => '1'
            ],
            [
                'name' => "Irish",
                'status' => '1'
            ],
            [
                'name' => "Italian",
                'status' => '1'
            ],
            [
                'name' => "Italian (Italy)",
                'status' => '1'
            ],
            [
                'name' => "Italian (Switzerland)",
                'status' => '1'
            ],
            [
                'name' => "Japanese",
                'status' => '1'
            ],
            [
                'name' => "Kannada",
                'status' => '1'
            ],
            [
                'name' => "Kazakh",
                'status' => '1'
            ],
            [
                'name' => "Khmer",
                'status' => '1'
            ],
            [
                'name' => "Korean",
                'status' => '1'
            ],
            [
                'name' => "Kurdish",
                'status' => '1'
            ],
            [
                'name' => "Kyrgyz",
                'status' => '1'
            ],
            [
                'name' => "Lao",
                'status' => '1'
            ],
            [
                'name' => "Latin",
                'status' => '1'
            ],
            [
                'name' => "Latvian",
                'status' => '1'
            ],
            [
                'name' => "Lingala",
                'status' => '1'
            ],
            [
                'name' => "Lithuanian",
                'status' => '1'
            ],
            [
                'name' => "Macedonian",
                'status' => '1'
            ],
            [
                'name' => "Malay",
                'status' => '1'
            ],
            [
                'name' => "Malayalam",
                'status' => '1'
            ],
            [
                'name' => "Maltese",
                'status' => '1'
            ],
            [
                'name' => "Marathi",
                'status' => '1'
            ],
            [
                'name' => "Mongolian",
                'status' => '1'
            ],
            [
                'name' => "Nepali",
                'status' => '1'
            ],
            [
                'name' => "Norwegian",
                'status' => '1'
            ],
            [
                'name' => "Norwegian Bokmål",
                'status' => '1'
            ],
            [
                'name' => "Norwegian Nynorsk",
                'status' => '1'
            ],
            [
                'name' => "Occitan",
                'status' => '1'
            ],
            [
                'name' => "Oriya",
                'status' => '1'
            ],
            [
                'name' => "Oromo",
                'status' => '1'
            ],
            [
                'name' => "Pashto",
                'status' => '1'
            ],
            [
                'name' => "Persian",
                'status' => '1'
            ],
            [
                'name' => "Polish",
                'status' => '1'
            ],
            [
                'name' => "Portuguese",
                'status' => '1'
            ],
            [
                'name' => "Portuguese (Brazil)",
                'status' => '1'
            ],
            [
                'name' => "Portuguese (Portugal)",
                'status' => '1'
            ],
            [
                'name' => "Punjabi",
                'status' => '1'
            ],
            [
                'name' => "Quechua",
                'status' => '1'
            ],
            [
                'name' => "Romanian",
                'status' => '1'
            ],
            [
                'name' => "Romanian (Moldova)",
                'status' => '1'
            ],
            [
                'name' => "Romansh",
                'status' => '1'
            ],
            [
                'name' => "Russian",
                'status' => '1'
            ],
            [
                'name' => "Scottish Gaelic",
                'status' => '1'
            ],
            [
                'name' => "Serbian",
                'status' => '1'
            ],
            [
                'name' => "Serbo",
                'status' => '1'
            ],
            [
                'name' => "Shona",
                'status' => '1'
            ],
            [
                'name' => "Sindhi",
                'status' => '1'
            ],
            [
                'name' => "Sinhala",
                'status' => '1'
            ],
            [
                'name' => "Slovak",
                'status' => '1'
            ],
            [
                'name' => "Slovenian",
                'status' => '1'
            ],
            [
                'name' => "Somali",
                'status' => '1'
            ],
            [
                'name' => "Southern Sotho",
                'status' => '1'
            ],
            [
                'name' => "Spanish",
                'status' => '1'
            ],
            [
                'name' => "Spanish",
                'status' => '1'
            ],
            [
                'name' => "Sundanese",
                'status' => '1'
            ],
            [
                'name' => "Swahili",
                'status' => '1'
            ],
            [
                'name' => "Swedish",
                'status' => '1'
            ],
            [
                'name' => "Tajik",
                'status' => '1'
            ],
            [
                'name' => "Tamil",
                'status' => '1'
            ],
            [
                'name' => "Tatar",
                'status' => '1'
            ],
            [
                'name' => "Telugu",
                'status' => '1'
            ],
            [
                'name' => "Thai",
                'status' => '1'
            ],
            [
                'name' => "Tigrinya",
                'status' => '1'
            ],
            [
                'name' => "Tongan",
                'status' => '1'
            ],
            [
                'name' => "Turkish",
                'status' => '1'
            ],
            [
                'name' => "Turkmen",
                'status' => '1'
            ],
            [
                'name' => "Twi",
                'status' => '1'
            ],
            [
                'name' => "Ukrainian",
                'status' => '1'
            ],
            [
                'name' => "Urdu",
                'status' => '1'
            ],
            [
                'name' => "Uyghur",
                'status' => '1'
            ],
            [
                'name' => "Uzbek",
                'status' => '1'
            ],
            [
                'name' => "Vietnamese",
                'status' => '1'
            ],
            [
                'name' => "Walloon",
                'status' => '1'
            ],
            [
                'name' => "Welsh",
                'status' => '1'
            ],
            [
                'name' => "Western Frisian",
                'status' => '1'
            ],
            [
                'name' => "Xhosa",
                'status' => '1'
            ],
            [
                'name' => "Yiddish",
                'status' => '1'
            ],
            [
                'name' => "Yoruba",
                'status' => '1'
            ],
            [
                'name' => "Zulu",
                'status' => '1'
            ],
        ];
        Language::insert($languages);
    }
}
