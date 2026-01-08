import { useState } from "react";
import { ExternalLink } from "lucide-react";
import { cn } from "@/lib/utils";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";

interface Feature {
  label: string;
  value: string;
}

interface PropertyKeyFeaturesProps {
  features: Feature[];
  maxVisible?: number;
  className?: string;
}

const PropertyKeyFeatures = ({
  features,
  maxVisible = 8,
  className,
}: PropertyKeyFeaturesProps) => {
  const [showAll, setShowAll] = useState(false);
  const [showModal, setShowModal] = useState(false);

  const visibleFeatures = showAll ? features : features.slice(0, maxVisible);
  const hasMore = features.length > maxVisible;

  const handleMoreClick = () => {
    if (features.length > maxVisible) {
      setShowModal(true);
    } else {
      setShowAll(true);
    }
  };

  return (
    <>
      <div
        className={cn(
          "grid grid-cols-2 rounded-xl",
          "px-4 py-4 gap-3",
          "md:px-6 md:py-4 md:gap-4",
          className
        )}
        style={{
          backgroundColor: "#FAFAFA",
          borderRadius: "12px",
        }}
      >
        {visibleFeatures.map((feature, index) => (
          <div
            key={index}
            className="flex flex-col"
            style={{
              marginBottom: index < visibleFeatures.length - 2 ? "12px" : "0",
            }}
          >
            <span
              style={{
                fontFamily: "Inter, sans-serif",
                fontWeight: 400,
                fontSize: "13px",
                color: "#616161",
                marginBottom: "4px",
              }}
            >
              {feature.label}
            </span>
            <span
              style={{
                fontFamily: "Inter, sans-serif",
                fontWeight: 500,
                fontSize: "14px",
                color: "#0F0F0F",
                wordBreak: "break-word",
              }}
            >
              {feature.value}
            </span>
          </div>
        ))}

        {/* More Button */}
        {hasMore && !showAll && (
          <button
            onClick={handleMoreClick}
            className={cn(
              "col-span-2 flex items-center gap-1 justify-start",
              "text-[#2563EB] hover:underline",
              "cursor-pointer transition-all",
              "mt-2"
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
            aria-label="Показать все параметры"
          >
            Подробнее
            <ExternalLink className="w-4 h-4" />
          </button>
        )}
      </div>

      {/* Full Features Modal */}
      <Dialog open={showModal} onOpenChange={setShowModal}>
        <DialogContent className="sm:max-w-2xl max-h-[80vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Все параметры</DialogTitle>
          </DialogHeader>

          <div
            className="grid grid-cols-2 gap-4 mt-4"
            style={{
              gap: "16px",
            }}
          >
            {features.map((feature, index) => (
              <div key={index} className="flex flex-col">
                <span
                  style={{
                    fontFamily: "Inter, sans-serif",
                    fontWeight: 400,
                    fontSize: "13px",
                    color: "#616161",
                    marginBottom: "4px",
                  }}
                >
                  {feature.label}
                </span>
                <span
                  style={{
                    fontFamily: "Inter, sans-serif",
                    fontWeight: 500,
                    fontSize: "14px",
                    color: "#0F0F0F",
                    wordBreak: "break-word",
                  }}
                >
                  {feature.value}
                </span>
              </div>
            ))}
          </div>
        </DialogContent>
      </Dialog>
    </>
  );
};

export default PropertyKeyFeatures;

