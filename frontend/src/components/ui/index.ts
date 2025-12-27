// LiveGrid UI Components Library v3.0
// Unified design system with consistent tokens, states, and accessibility
// All components use design tokens from index.css and tailwind.config.ts

// ===== CORE COMPONENTS =====
export { Button, buttonVariants, type ButtonProps } from "./button";
export { Input, inputVariants, type InputProps } from "./input";
export {
  Card,
  CardHeader,
  CardFooter,
  CardTitle,
  CardDescription,
  CardContent,
  cardVariants,
  type CardProps,
} from "./card";
export { Badge, badgeVariants, type BadgeProps } from "./badge";
export { Chip, chipVariants, type ChipProps } from "./chip";
export { Icon, iconVariants, type IconProps } from "./icon";

// ===== FORM COMPONENTS =====
export { Textarea, type TextareaProps } from "./textarea";
export {
  Select,
  SelectGroup,
  SelectValue,
  SelectTrigger,
  SelectContent,
  SelectLabel,
  SelectItem,
  SelectSeparator,
  SelectScrollUpButton,
  SelectScrollDownButton,
} from "./select";
export { Checkbox } from "./checkbox";
export { RadioGroup, RadioGroupItem } from "./radio-group";
export { Toggle, toggleVariants } from "./toggle";
export { Slider } from "./slider";
export { Switch } from "./switch";
export { Label } from "./label";

// ===== FEEDBACK COMPONENTS =====
export { Toaster } from "./toaster";
export {
  Toast,
  ToastAction,
  ToastClose,
  ToastDescription,
  ToastProvider,
  ToastTitle,
  ToastViewport,
} from "./toast";
export { Skeleton } from "./skeleton";
export { Progress } from "./progress";

// ===== OVERLAY COMPONENTS =====
export {
  Dialog,
  DialogPortal,
  DialogOverlay,
  DialogTrigger,
  DialogClose,
  DialogContent,
  DialogHeader,
  DialogFooter,
  DialogTitle,
  DialogDescription,
} from "./dialog";
export {
  Sheet,
  SheetPortal,
  SheetOverlay,
  SheetTrigger,
  SheetClose,
  SheetContent,
  SheetHeader,
  SheetFooter,
  SheetTitle,
  SheetDescription,
} from "./sheet";
export {
  Popover,
  PopoverTrigger,
  PopoverContent,
} from "./popover";
export {
  Tooltip,
  TooltipTrigger,
  TooltipContent,
  TooltipProvider,
} from "./tooltip";

// ===== NAVIGATION COMPONENTS =====
export { Tabs, TabsList, TabsTrigger, TabsContent } from "./tabs";
export {
  Accordion,
  AccordionItem,
  AccordionTrigger,
  AccordionContent,
} from "./accordion";
export {
  Breadcrumb,
  BreadcrumbList,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbPage,
  BreadcrumbSeparator,
  BreadcrumbEllipsis,
} from "./breadcrumb";

// ===== DATA DISPLAY COMPONENTS =====
export {
  Table,
  TableHeader,
  TableBody,
  TableFooter,
  TableHead,
  TableRow,
  TableCell,
  TableCaption,
} from "./table";
export { ScrollArea, ScrollBar } from "./scroll-area";
export { Separator } from "./separator";
export { Avatar, AvatarImage, AvatarFallback } from "./avatar";
export { AspectRatio } from "./aspect-ratio";

// ===== LAYOUT COMPONENTS =====
export {
  Carousel,
  CarouselContent,
  CarouselItem,
  CarouselPrevious,
  CarouselNext,
} from "./carousel";

/*
 * LiveGrid Design System v3.0 Checklist
 * =====================================
 * 
 * ✅ Colors:
 *    - Accent: #4AB3FF (hsl 205 100% 64%)
 *    - Accent Hover: #2DA6FF (hsl 205 100% 58%)
 *    - Accent Soft: rgba(74, 179, 255, 0.12)
 *    - Text Primary: #0A2342 (hsl 213 66% 15%)
 *    - Text Secondary: rgba(10, 35, 66, 0.66)
 *    - Text Muted: rgba(10, 35, 66, 0.44)
 *    - Border: rgba(10, 35, 66, 0.12)
 *    - Background: #FFFFFF
 *    - Surface Hover: rgba(10, 35, 66, 0.03)
 *    - Success: #10B981
 *    - Warning: #F59E0B
 *    - Error: #EF4444
 * 
 * ✅ Typography:
 *    - Font Family: Inter
 *    - H1: 32px / 700 / -0.01em
 *    - H2: 24px / 700 / -0.01em
 *    - H3: 20px / 600 / -0.01em
 *    - Body: 14px / 400 / normal
 *    - Caption: 12px / 500 / normal
 *    - Line Height: 1.5
 * 
 * ✅ Border Radius:
 *    - Small: 8px (sm)
 *    - Medium: 12px (md)
 *    - Large: 16px (lg)
 *    - Full: 999px
 * 
 * ✅ Shadows:
 *    - xs: 0 1px 3px rgba(0,0,0,0.04)
 *    - sm: 0 2px 10px rgba(0,0,0,0.06)
 *    - md: 0 8px 24px rgba(0,0,0,0.10)
 *    - lg: 0 16px 48px rgba(0,0,0,0.12)
 * 
 * ✅ Transitions:
 *    - Fast: 150ms ease-out
 *    - Normal: 180ms ease-out
 *    - Smooth: 300ms cubic-bezier(0.4, 0, 0.2, 1)
 * 
 * ✅ Component States:
 *    - Normal / Hover / Active / Disabled / Focus
 *    - Loading states for buttons
 *    - Error states for inputs
 * 
 * ✅ Accessibility:
 *    - Focus rings visible
 *    - ARIA labels supported
 *    - Keyboard navigation
 *    - Color contrast 4.5:1+
 */
