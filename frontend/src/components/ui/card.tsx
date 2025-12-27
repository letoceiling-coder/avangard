import * as React from "react";
import { cva, type VariantProps } from "class-variance-authority";

import { cn } from "@/lib/utils";

const cardVariants = cva(
  "bg-card text-card-foreground border border-border/60 transition-all duration-normal ease-out",
  {
    variants: {
      radius: {
        sm: "rounded-xl",
        md: "rounded-2xl",
        lg: "rounded-[20px]",
        xl: "rounded-3xl",
      },
      shadow: {
        none: "",
        sm: "shadow-[0_4px_16px_rgba(10,35,66,0.04)]",
        md: "shadow-[0_8px_24px_rgba(10,35,66,0.06),0_2px_8px_rgba(10,35,66,0.04)]",
      },
      padding: {
        none: "",
        sm: "p-2",
        md: "p-3",
        lg: "p-4",
      },
      hoverable: {
        true: "cursor-pointer hover:-translate-y-1 hover:shadow-[0_20px_48px_rgba(10,35,66,0.10),0_8px_20px_rgba(10,35,66,0.06)] hover:border-primary/20",
        false: "",
      },
      interactive: {
        true: "cursor-pointer hover:-translate-y-1 hover:shadow-[0_16px_40px_rgba(10,35,66,0.08)] hover:ring-1 hover:ring-primary/10 active:translate-y-0 active:shadow-sm",
        false: "",
      },
      glow: {
        true: "hover:shadow-glow hover:border-primary/30",
        false: "",
      },
    },
    defaultVariants: {
      radius: "lg",
      shadow: "md",
      padding: "none",
      hoverable: false,
      interactive: false,
      glow: false,
    },
  }
);

export interface CardProps
  extends React.HTMLAttributes<HTMLDivElement>,
    VariantProps<typeof cardVariants> {}

const Card = React.forwardRef<HTMLDivElement, CardProps>(
  (
    {
      className,
      radius,
      shadow,
      padding,
      hoverable,
      interactive,
      glow,
      ...props
    },
    ref
  ) => (
    <div
      ref={ref}
      className={cn(
        cardVariants({ radius, shadow, padding, hoverable, interactive, glow }),
        className
      )}
      {...props}
    />
  )
);
Card.displayName = "Card";

const CardHeader = React.forwardRef<
  HTMLDivElement,
  React.HTMLAttributes<HTMLDivElement>
>(({ className, ...props }, ref) => (
  <div
    ref={ref}
    className={cn("flex flex-col space-y-1.5 p-4", className)}
    {...props}
  />
));
CardHeader.displayName = "CardHeader";

const CardTitle = React.forwardRef<
  HTMLParagraphElement,
  React.HTMLAttributes<HTMLHeadingElement>
>(({ className, ...props }, ref) => (
  <h3
    ref={ref}
    className={cn(
      "text-h3 font-semibold leading-tight tracking-tight text-title",
      className
    )}
    {...props}
  />
));
CardTitle.displayName = "CardTitle";

const CardDescription = React.forwardRef<
  HTMLParagraphElement,
  React.HTMLAttributes<HTMLParagraphElement>
>(({ className, ...props }, ref) => (
  <p
    ref={ref}
    className={cn("text-body text-muted-foreground", className)}
    {...props}
  />
));
CardDescription.displayName = "CardDescription";

const CardContent = React.forwardRef<
  HTMLDivElement,
  React.HTMLAttributes<HTMLDivElement>
>(({ className, ...props }, ref) => (
  <div ref={ref} className={cn("p-4 pt-0", className)} {...props} />
));
CardContent.displayName = "CardContent";

const CardFooter = React.forwardRef<
  HTMLDivElement,
  React.HTMLAttributes<HTMLDivElement>
>(({ className, ...props }, ref) => (
  <div
    ref={ref}
    className={cn("flex items-center p-4 pt-0", className)}
    {...props}
  />
));
CardFooter.displayName = "CardFooter";

export {
  Card,
  CardHeader,
  CardFooter,
  CardTitle,
  CardDescription,
  CardContent,
  cardVariants,
};