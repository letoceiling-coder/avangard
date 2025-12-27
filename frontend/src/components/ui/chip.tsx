import * as React from "react";
import { cva, type VariantProps } from "class-variance-authority";

import { cn } from "@/lib/utils";

const chipVariants = cva(
  "inline-flex items-center gap-1.5 rounded-full font-medium cursor-pointer transition-all duration-normal ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 disabled:cursor-not-allowed disabled:opacity-50",
  {
    variants: {
      variant: {
        default: "bg-muted text-foreground border border-transparent hover:bg-primary hover:text-primary-foreground",
        filled: "bg-primary text-primary-foreground shadow-sm hover:bg-accent-hover",
        outline: "bg-transparent border border-border text-foreground hover:border-primary hover:text-primary",
      },
      size: {
        sm: "h-7 px-2.5 py-1 text-[11px] [&_svg]:size-3.5",
        md: "h-9 px-3 py-1.5 text-xs [&_svg]:size-4",
        lg: "h-10 px-4 py-2 text-sm [&_svg]:size-4",
      },
      active: {
        true: "",
        false: "",
      },
    },
    compoundVariants: [
      {
        variant: "default",
        active: true,
        className: "bg-primary/10 border-primary/20 text-primary",
      },
      {
        variant: "outline",
        active: true,
        className: "border-primary text-primary bg-primary/5",
      },
    ],
    defaultVariants: {
      variant: "default",
      size: "md",
      active: false,
    },
  }
);

export interface ChipProps
  extends React.ButtonHTMLAttributes<HTMLButtonElement>,
    VariantProps<typeof chipVariants> {
  label: string;
  icon?: React.ReactNode;
  onRemove?: () => void;
}

const Chip = React.forwardRef<HTMLButtonElement, ChipProps>(
  (
    {
      className,
      variant,
      size,
      active,
      label,
      icon,
      onRemove,
      disabled,
      ...props
    },
    ref
  ) => {
    return (
      <button
        ref={ref}
        type="button"
        className={cn(chipVariants({ variant, size, active }), className)}
        disabled={disabled}
        {...props}
      >
        {icon && <span className="flex-shrink-0">{icon}</span>}
        <span>{label}</span>
        {onRemove && (
          <span
            role="button"
            tabIndex={0}
            onClick={(e) => {
              e.stopPropagation();
              onRemove();
            }}
            onKeyDown={(e) => {
              if (e.key === "Enter" || e.key === " ") {
                e.stopPropagation();
                onRemove();
              }
            }}
            className="ml-0.5 rounded-full p-0.5 hover:bg-foreground/10 transition-colors"
          >
            <svg
              width="12"
              height="12"
              viewBox="0 0 12 12"
              fill="none"
              stroke="currentColor"
              strokeWidth="2"
              strokeLinecap="round"
            >
              <path d="M3 3l6 6M9 3l-6 6" />
            </svg>
          </span>
        )}
      </button>
    );
  }
);
Chip.displayName = "Chip";

export { Chip, chipVariants };
