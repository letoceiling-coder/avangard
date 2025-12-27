import * as React from "react";
import { cva, type VariantProps } from "class-variance-authority";

import { cn } from "@/lib/utils";

const inputVariants = cva(
  "flex w-full bg-card text-foreground transition-all duration-normal ease-out placeholder:text-muted-foreground placeholder:font-normal focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50",
  {
    variants: {
      variant: {
        default:
          "border border-border rounded-sm shadow-xs hover:border-border/80 focus:border-primary focus:border-2 focus:ring-2 focus:ring-primary/10",
        error:
          "border border-destructive rounded-sm shadow-xs focus:border-destructive focus:border-2 focus:ring-2 focus:ring-destructive/10",
        search:
          "border-0 bg-transparent focus:ring-0",
      },
      inputSize: {
        sm: "h-9 px-3 py-2 text-xs",
        md: "h-11 px-4 py-2 text-sm",
        lg: "h-12 px-5 py-3 text-base",
      },
    },
    defaultVariants: {
      variant: "default",
      inputSize: "md",
    },
  }
);

export interface InputProps
  extends Omit<React.InputHTMLAttributes<HTMLInputElement>, "size">,
    VariantProps<typeof inputVariants> {
  label?: string;
  error?: boolean;
  errorMessage?: string;
  startIcon?: React.ReactNode;
  endAdornment?: React.ReactNode;
  wrapperClassName?: string;
}

const Input = React.forwardRef<HTMLInputElement, InputProps>(
  (
    {
      className,
      type,
      variant,
      inputSize,
      label,
      error,
      errorMessage,
      startIcon,
      endAdornment,
      wrapperClassName,
      id,
      ...props
    },
    ref
  ) => {
    const inputId = id || React.useId();
    const currentVariant = error ? "error" : variant;

    return (
      <div className={cn("flex flex-col gap-1.5", wrapperClassName)}>
        {label && (
          <label
            htmlFor={inputId}
            className="text-sm font-semibold text-foreground"
          >
            {label}
          </label>
        )}
        <div className="relative">
          {startIcon && (
            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground [&_svg]:size-5">
              {startIcon}
            </span>
          )}
          <input
            type={type}
            id={inputId}
            className={cn(
              inputVariants({ variant: currentVariant, inputSize }),
              startIcon && "pl-10",
              endAdornment && "pr-10",
              className
            )}
            ref={ref}
            {...props}
          />
          {endAdornment && (
            <span className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">
              {endAdornment}
            </span>
          )}
        </div>
        {error && errorMessage && (
          <p className="text-xs font-medium text-destructive">{errorMessage}</p>
        )}
      </div>
    );
  }
);
Input.displayName = "Input";

export { Input, inputVariants };