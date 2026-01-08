import { useState } from "react";
import { ExternalLink } from "lucide-react";
import { cn } from "@/lib/utils";

interface PropertyDescriptionProps {
  description: string;
  maxLines?: number;
  className?: string;
}

const PropertyDescription = ({
  description,
  maxLines = 5,
  className,
}: PropertyDescriptionProps) => {
  const [isExpanded, setIsExpanded] = useState(false);

  // Приблизительная оценка: одна строка ≈ 60-80 символов при ширине контейнера
  const estimatedCharsPerLine = 70;
  const maxChars = estimatedCharsPerLine * maxLines;
  const shouldTruncate = description.length > maxChars;
  const truncatedText = shouldTruncate && !isExpanded
    ? description.slice(0, maxChars) + "..."
    : description;

  return (
    <div
      className={cn(
        "px-4 py-4",
        "md:px-6 md:py-4",
        className
      )}
      style={{
        backgroundColor: "#FFFFFF",
      }}
    >
      {/* Title */}
      <h3
        style={{
          fontFamily: "Inter, sans-serif",
          fontWeight: 600,
          fontSize: "14px",
          color: "#0F0F0F",
          marginBottom: "12px",
        }}
      >
        О помещении
      </h3>

      {/* Description Text */}
      <p
        className={cn(
          !isExpanded && shouldTruncate && "line-clamp-5"
        )}
        style={{
          fontFamily: "Inter, sans-serif",
          fontWeight: 400,
          fontSize: "14px",
          color: "#616161",
          lineHeight: "1.6",
          marginBottom: shouldTruncate && !isExpanded ? "12px" : "0",
          wordWrap: "break-word",
        }}
      >
        {truncatedText}
      </p>

      {/* Read More Button */}
      {shouldTruncate && (
        <button
          onClick={() => setIsExpanded(!isExpanded)}
          className={cn(
            "flex items-center gap-1 mt-3",
            "text-[#2563EB] hover:underline",
            "cursor-pointer transition-all"
          )}
          style={{
            fontFamily: "Inter, sans-serif",
            fontWeight: 500,
            fontSize: "14px",
            color: "#2563EB",
            background: "transparent",
            border: "none",
            padding: 0,
          }}
          aria-label={isExpanded ? "Скрыть описание" : "Читать полностью"}
        >
          {isExpanded ? "Скрыть" : "Читать полностью"}
          {!isExpanded && <ExternalLink className="w-4 h-4" />}
        </button>
      )}
    </div>
  );
};

export default PropertyDescription;

