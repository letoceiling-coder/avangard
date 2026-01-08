import { useState } from "react";
import { ChevronDown, ChevronRight } from "lucide-react";
import { cn } from "@/lib/utils";

interface Parameter {
  label: string;
  value: string;
}

interface Section {
  title: string;
  parameters: Parameter[];
}

interface PropertyFullDetailsProps {
  sections: Section[];
  className?: string;
}

const PropertyFullDetails = ({
  sections,
  className,
}: PropertyFullDetailsProps) => {
  const [expandedSections, setExpandedSections] = useState<Set<number>>(new Set());

  const toggleSection = (index: number) => {
    setExpandedSections((prev) => {
      const newSet = new Set(prev);
      if (newSet.has(index)) {
        newSet.delete(index);
      } else {
        newSet.add(index);
      }
      return newSet;
    });
  };

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
      {sections.map((section, sectionIndex) => {
        const isExpanded = expandedSections.has(sectionIndex);

        return (
          <div
            key={sectionIndex}
            className={cn(
              sectionIndex !== sections.length - 1 && "border-b border-[#EEEEEE]",
              sectionIndex !== 0 && "pt-4",
              sectionIndex !== sections.length - 1 && "pb-4"
            )}
          >
            {/* Accordion Header */}
            <button
              onClick={() => toggleSection(sectionIndex)}
              className={cn(
                "w-full flex items-center justify-between",
                "px-3 py-3 rounded-lg",
                "hover:bg-[#F3F4F6]",
                "transition-all duration-200",
                "cursor-pointer"
              )}
              style={{
                fontFamily: "Inter, sans-serif",
                fontWeight: 600,
                fontSize: "14px",
                color: "#0F0F0F",
                background: "transparent",
                border: "none",
                borderRadius: "8px",
              }}
              aria-label={`${isExpanded ? "Скрыть" : "Показать"} ${section.title}`}
              aria-expanded={isExpanded}
            >
              <span>{section.title}</span>
              <ChevronDown
                className={cn(
                  "w-4 h-4 text-[#616161] transition-transform duration-300",
                  isExpanded ? "rotate-0" : "-rotate-90"
                )}
              />
            </button>

            {/* Accordion Content */}
            <div
              className={cn(
                "overflow-hidden transition-all duration-300 ease-in-out",
                isExpanded ? "max-h-[2000px] opacity-100" : "max-h-0 opacity-0"
              )}
            >
              <div
                className="pt-3 px-3"
                style={{
                  backgroundColor: "#FAFAFA",
                  padding: "12px",
                  display: "flex",
                  flexDirection: "column",
                  gap: "12px",
                }}
              >
                {section.parameters.map((param, paramIndex) => (
                  <div
                    key={paramIndex}
                    className="flex items-center justify-between gap-4"
                  >
                    <span
                      style={{
                        fontFamily: "Inter, sans-serif",
                        fontWeight: 400,
                        fontSize: "13px",
                        color: "#616161",
                        flexShrink: 0,
                      }}
                    >
                      {param.label}
                    </span>
                    <span
                      className="text-right flex-1 min-w-0"
                      style={{
                        fontFamily: "Inter, sans-serif",
                        fontWeight: 500,
                        fontSize: "14px",
                        color: "#0F0F0F",
                        wordBreak: "break-word",
                      }}
                    >
                      {param.value}
                    </span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
};

export default PropertyFullDetails;

