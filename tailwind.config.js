export default {
    content: [
        "../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/**/*.blade.php",
        "../../storage/framework/views/**/*.php",
        "../**/*.blade.php",
        "../**/*.js",

        "../../bootstrap/**/*.{blade.php,css,html,js,php}",
        "../../modules/**/*.{blade.php,css,html,js,php}",
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: [
                    "Instrument Sans",
                    "ui-sans-serif",
                    "system-ui",
                    "sans-serif",
                    "Apple Color Emoji",
                    "Segoe UI Emoji",
                    "Segoe UI Symbol",
                    "Noto Color Emoji",
                ],
            },
        },
    },
    plugins: [],
};
