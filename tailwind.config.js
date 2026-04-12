/** @type {import('tailwindcss').Config} */
export default {
    content: ["./resources/**/*.blade.php", "./resources/**/*.js"],
    theme: {
        // Change default values here
        container: {
            center: true,
            padding: "2rem",
        },
        // Custom classes here
        extend: {
            fontFamily: {
                agenda: ["'IBM Plex Mono'", "monospace"],
            },
            textUnderlineOffset: {
                4: "4px",
            },
        },
    },
    daisyui: {
        themes: ["winter"],
    },
    plugins: [
        require("daisyui"),
        require("@tailwindcss/typography"),
        require("@tailwindcss/forms"),
    ],
};
