import { cn } from "@/lib/utils";

interface LiveGridLogoProps {
  variant?: "light" | "dark";
  size?: "sm" | "md" | "lg";
  showText?: boolean;
  className?: string;
}

const LiveGridLogo = ({ 
  variant = "light", 
  size = "md", 
  showText = true,
  className 
}: LiveGridLogoProps) => {
  const sizes = {
    sm: { icon: 28, text: "text-lg" },
    md: { icon: 36, text: "text-xl" },
    lg: { icon: 48, text: "text-2xl" },
  };

  const { icon, text } = sizes[size];

  return (
    <div className={cn("flex items-center gap-2.5 group", className)}>
      {/* Logo Icon */}
      <div className="relative">
        <svg
          width={icon}
          height={icon}
          viewBox="0 0 48 48"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
          className="transition-transform duration-300 group-hover:scale-105"
        >
          {/* Glow effect */}
          <defs>
            <filter id="glow" x="-50%" y="-50%" width="200%" height="200%">
              <feGaussianBlur stdDeviation="2" result="coloredBlur"/>
              <feMerge>
                <feMergeNode in="coloredBlur"/>
                <feMergeNode in="SourceGraphic"/>
              </feMerge>
            </filter>
            <linearGradient id="primaryGradient" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stopColor="#00ADEF"/>
              <stop offset="100%" stopColor="#00D2FF"/>
            </linearGradient>
          </defs>
          
          {/* Grid squares with glow */}
          <g filter="url(#glow)" className="group-hover:opacity-100 opacity-90 transition-opacity">
            <rect x="4" y="4" width="18" height="18" rx="4" fill="url(#primaryGradient)"/>
            <rect x="26" y="4" width="18" height="18" rx="4" fill="url(#primaryGradient)" fillOpacity="0.7"/>
            <rect x="4" y="26" width="18" height="18" rx="4" fill="url(#primaryGradient)" fillOpacity="0.5"/>
            <rect x="26" y="26" width="18" height="18" rx="4" fill="url(#primaryGradient)" fillOpacity="0.3"/>
          </g>
        </svg>
        
        {/* Hover glow */}
        <div className="absolute inset-0 bg-primary/20 blur-xl rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
      </div>

      {/* Logo Text */}
      {showText && (
        <div className="flex flex-col">
          <span className={cn(
            "font-display font-bold tracking-tight leading-none transition-colors duration-300",
            text,
            variant === "light" ? "text-foreground" : "text-white"
          )}>
            Live<span className="text-gradient">Grid</span>
          </span>
          <span className={cn(
            "text-[10px] font-medium tracking-wider uppercase",
            variant === "light" ? "text-muted-foreground" : "text-white/60"
          )}>
            Недвижимость
          </span>
        </div>
      )}
    </div>
  );
};

export default LiveGridLogo;
