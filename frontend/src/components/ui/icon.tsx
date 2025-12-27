import * as React from "react";
import { cva, type VariantProps } from "class-variance-authority";
import { type LucideIcon } from "lucide-react";

import { cn } from "@/lib/utils";

const iconVariants = cva("flex-shrink-0", {
  variants: {
    size: {
      xs: "h-3.5 w-3.5",
      sm: "h-4 w-4",
      md: "h-5 w-5",
      lg: "h-6 w-6",
      xl: "h-8 w-8",
    },
    variant: {
      default: "text-current",
      primary: "text-primary",
      muted: "text-muted-foreground",
      success: "text-success",
      warning: "text-warning",
      error: "text-destructive",
    },
  },
  defaultVariants: {
    size: "md",
    variant: "default",
  },
});

export interface IconProps extends VariantProps<typeof iconVariants> {
  icon: LucideIcon;
  strokeWidth?: number;
  className?: string;
}

const Icon = React.forwardRef<SVGSVGElement, IconProps>(
  ({ className, size, variant, icon: IconComponent, strokeWidth = 2, ...props }, ref) => {
    return (
      <IconComponent
        ref={ref}
        className={cn(iconVariants({ size, variant }), className)}
        strokeWidth={strokeWidth}
        {...props}
      />
    );
  }
);
Icon.displayName = "Icon";

export { Icon, iconVariants };
