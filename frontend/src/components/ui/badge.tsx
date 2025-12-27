import * as React from "react";
import { cva, type VariantProps } from "class-variance-authority";

import { cn } from "@/lib/utils";

const badgeVariants = cva(
  "inline-flex items-center rounded-full border-0 font-medium transition-all duration-normal",
  {
    variants: {
      variant: {
        info: "bg-primary/10 text-primary",
        success: "bg-success/10 text-success",
        warning: "bg-warning/10 text-warning",
        error: "bg-destructive/10 text-destructive",
        default: "bg-muted text-muted-foreground",
        outline: "border border-border text-foreground bg-transparent",
      },
      size: {
        sm: "px-2 py-0.5 text-[10px]",
        md: "px-2 py-1 text-caption",
        lg: "px-3 py-1.5 text-body",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "md",
    },
  }
);

export interface BadgeProps
  extends React.HTMLAttributes<HTMLSpanElement>,
    VariantProps<typeof badgeVariants> {
  label?: string;
}

function Badge({ className, variant, size, label, children, ...props }: BadgeProps) {
  return (
    <span className={cn(badgeVariants({ variant, size }), className)} {...props}>
      {label || children}
    </span>
  );
}

export { Badge, badgeVariants };