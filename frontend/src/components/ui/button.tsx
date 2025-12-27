import * as React from "react";
import { Slot } from "@radix-ui/react-slot";
import { cva, type VariantProps } from "class-variance-authority";
import { Loader2 } from "lucide-react";

import { cn } from "@/lib/utils";

const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap font-semibold transition-all duration-normal ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0",
  {
    variants: {
      variant: {
        primary:
          "bg-primary text-primary-foreground shadow-sm hover:bg-accent-hover hover:opacity-90 hover:-translate-y-0.5 hover:shadow-md active:bg-accent-active active:scale-[0.98] active:shadow-sm",
        secondary:
          "bg-card text-foreground border border-border hover:border-primary hover:text-primary hover:bg-primary/5 active:bg-primary/10",
        ghost:
          "text-foreground hover:bg-muted hover:text-primary hover:opacity-90 active:bg-surface-hover",
        danger:
          "bg-destructive text-destructive-foreground shadow-sm hover:bg-destructive/90 hover:opacity-90 hover:-translate-y-0.5 hover:shadow-md active:scale-[0.98]",
        outline:
          "border border-border bg-background hover:bg-muted hover:text-primary hover:border-primary/50 active:bg-surface-hover",
        link:
          "text-primary underline-offset-4 hover:underline hover:opacity-90",
      },
      size: {
        sm: "min-h-[44px] h-9 px-3 py-2 text-xs rounded-sm [&_svg]:size-3",
        md: "min-h-[44px] h-11 px-4 py-2 text-sm rounded-md [&_svg]:size-4",
        lg: "min-h-[44px] h-12 px-6 py-3 text-base rounded-md [&_svg]:size-5",
        icon: "min-h-[44px] min-w-[44px] h-10 w-10 rounded-md [&_svg]:size-4",
      },
      fullWidth: {
        true: "w-full",
        false: "",
      },
    },
    defaultVariants: {
      variant: "primary",
      size: "md",
      fullWidth: false,
    },
  }
);

export interface ButtonProps
  extends React.ButtonHTMLAttributes<HTMLButtonElement>,
    VariantProps<typeof buttonVariants> {
  asChild?: boolean;
  loading?: boolean;
  leftIcon?: React.ReactNode;
  rightIcon?: React.ReactNode;
}

const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
  (
    {
      className,
      variant,
      size,
      fullWidth,
      asChild = false,
      loading = false,
      leftIcon,
      rightIcon,
      children,
      disabled,
      ...props
    },
    ref
  ) => {
    const Comp = asChild ? Slot : "button";
    const isDisabled = disabled || loading;

    return (
      <Comp
        className={cn(buttonVariants({ variant, size, fullWidth, className }))}
        ref={ref}
        disabled={isDisabled}
        {...props}
      >
        {loading ? (
          <>
            <Loader2 className="animate-spin" />
            <span className="sr-only">Загрузка...</span>
          </>
        ) : (
          <>
            {leftIcon && <span className="flex-shrink-0">{leftIcon}</span>}
            {children}
            {rightIcon && <span className="flex-shrink-0">{rightIcon}</span>}
          </>
        )}
      </Comp>
    );
  }
);
Button.displayName = "Button";

export { Button, buttonVariants };