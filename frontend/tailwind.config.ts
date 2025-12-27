import type { Config } from "tailwindcss";

export default {
  darkMode: ["class"],
  content: ["./pages/**/*.{ts,tsx}", "./components/**/*.{ts,tsx}", "./app/**/*.{ts,tsx}", "./src/**/*.{ts,tsx}"],
  prefix: "",
  theme: {
    container: {
      center: true,
      padding: "1rem",
      screens: {
        sm: "640px",
        md: "768px",
        lg: "1024px",
        xl: "1280px",
        "2xl": "1400px",
      },
    },
    extend: {
      fontFamily: {
        sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'sans-serif'],
        display: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'sans-serif'],
      },
      fontSize: {
        'hero': ['42px', { lineHeight: '1.15', fontWeight: '600', letterSpacing: '-0.02em' }],
        'h1': ['40px', { lineHeight: '1.2', fontWeight: '600', letterSpacing: '-0.02em' }],
        'h2': ['28px', { lineHeight: '1.35', fontWeight: '600', letterSpacing: '-0.01em' }],
        'h3': ['20px', { lineHeight: '1.4', fontWeight: '600' }],
        'body': ['15px', { lineHeight: '1.5', fontWeight: '400' }],
        'body-lg': ['16px', { lineHeight: '1.5', fontWeight: '400' }],
        'caption': ['13px', { lineHeight: '1.4', fontWeight: '500' }],
        'small': ['12px', { lineHeight: '1.4', fontWeight: '400' }],
      },
      colors: {
        /* Core Brand Colors */
        accent: {
          DEFAULT: "hsl(205 100% 64%)",
          hover: "hsl(205 100% 54%)",
          active: "hsl(205 100% 49%)",
          soft: "hsl(205 100% 64% / 0.10)",
        },
        title: "hsl(213 66% 15%)",
        /* Shadcn Compatibility */
        border: "hsl(var(--border))",
        input: "hsl(var(--input))",
        ring: "hsl(var(--ring))",
        background: "hsl(var(--background))",
        foreground: "hsl(var(--foreground))",
        primary: {
          DEFAULT: "hsl(var(--primary))",
          foreground: "hsl(var(--primary-foreground))",
          glow: "hsl(var(--primary-glow))",
        },
        secondary: {
          DEFAULT: "hsl(var(--secondary))",
          foreground: "hsl(var(--secondary-foreground))",
        },
        destructive: {
          DEFAULT: "hsl(var(--destructive))",
          foreground: "hsl(var(--destructive-foreground))",
        },
        muted: {
          DEFAULT: "hsl(var(--muted))",
          foreground: "hsl(var(--muted-foreground))",
        },
        popover: {
          DEFAULT: "hsl(var(--popover))",
          foreground: "hsl(var(--popover-foreground))",
        },
        card: {
          DEFAULT: "hsl(var(--card))",
          foreground: "hsl(var(--card-foreground))",
        },
        /* Status Colors */
        success: {
          DEFAULT: "hsl(var(--success))",
          foreground: "hsl(var(--success-foreground))",
        },
        warning: {
          DEFAULT: "hsl(var(--warning))",
          foreground: "hsl(var(--warning-foreground))",
        },
        /* Extended Tokens */
        surface: {
          DEFAULT: "hsl(var(--surface))",
          hover: "hsl(210 33% 95%)",
        },
        "dark-blue": "hsl(var(--dark-blue))",
        "hero-bg": "hsl(var(--hero-bg))",
        "hero-surface": "hsl(var(--hero-surface))",
        "light-bg": "hsl(var(--light-bg))",
        "gray-soft": "hsl(var(--gray-soft))",
        /* Text Colors */
        text: {
          primary: "hsl(213 66% 15%)",
          secondary: "hsl(213 66% 15% / 0.66)",
          muted: "hsl(213 66% 15% / 0.44)",
        },
      },
      spacing: {
        '1': '8px',
        '2': '16px',
        '3': '24px',
        '4': '32px',
        '5': '40px',
      },
      borderRadius: {
        xs: "4px",
        sm: "10px",
        md: "12px",
        lg: "12px",
        xl: "12px",
        "2xl": "16px",
        "3xl": "20px",
        full: "999px",
      },
      boxShadow: {
        xs: "0 1px 2px rgba(0, 0, 0, 0.03)",
        sm: "0 2px 8px rgba(0, 0, 0, 0.04)",
        md: "0 8px 24px rgba(0, 0, 0, 0.06)",
        lg: "0 16px 48px rgba(0, 0, 0, 0.08)",
        xl: "0 24px 64px rgba(0, 0, 0, 0.10)",
        card: "0 2px 12px rgba(0, 0, 0, 0.04)",
        "card-hover": "0 8px 24px rgba(0, 0, 0, 0.08)",
        search: "0 8px 32px rgba(0, 0, 0, 0.06)",
        glow: "0 0 24px rgba(93, 169, 233, 0.20)",
        "glow-lg": "0 0 40px rgba(93, 169, 233, 0.30)",
        soft: "0 2px 8px rgba(0, 0, 0, 0.03)",
        elevated: "0 8px 24px rgba(0, 0, 0, 0.06)",
        header: "0 1px 3px rgba(0, 0, 0, 0.04)",
      },
      transitionDuration: {
        fast: "150ms",
        normal: "180ms",
        smooth: "300ms",
      },
      transitionTimingFunction: {
        DEFAULT: "ease-out",
        smooth: "cubic-bezier(0.4, 0, 0.2, 1)",
      },
      keyframes: {
        "accordion-down": {
          from: { height: "0" },
          to: { height: "var(--radix-accordion-content-height)" },
        },
        "accordion-up": {
          from: { height: "var(--radix-accordion-content-height)" },
          to: { height: "0" },
        },
        "fade-in": {
          "0%": { opacity: "0", transform: "translateY(8px)" },
          "100%": { opacity: "1", transform: "translateY(0)" },
        },
        "slide-up": {
          "0%": { opacity: "0", transform: "translateY(16px)" },
          "100%": { opacity: "1", transform: "translateY(0)" },
        },
        "slide-in-from-bottom": {
          "0%": { opacity: "0", transform: "translateY(100%)" },
          "100%": { opacity: "1", transform: "translateY(0)" },
        },
        shimmer: {
          "0%": { backgroundPosition: "-200% 0" },
          "100%": { backgroundPosition: "200% 0" },
        },
        pulse: {
          "0%, 100%": { opacity: "1" },
          "50%": { opacity: "0.5" },
        },
      },
      animation: {
        "accordion-down": "accordion-down 0.2s ease-out",
        "accordion-up": "accordion-up 0.2s ease-out",
        "fade-in": "fade-in 0.3s ease-out",
        "slide-up": "slide-up 0.4s ease-out",
        "slide-in-from-bottom-4": "slide-in-from-bottom 0.3s ease-out",
        shimmer: "shimmer 2s infinite linear",
        pulse: "pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite",
      },
    },
  },
  plugins: [require("tailwindcss-animate")],
} satisfies Config;