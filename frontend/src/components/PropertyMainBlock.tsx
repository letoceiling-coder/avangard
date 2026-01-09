import { cn } from "@/lib/utils";
import PropertyMediaGallery from "@/components/PropertyMediaGallery";
import PropertyPriceStatusBlock from "@/components/PropertyPriceStatusBlock";
import PropertyTitleBlock from "@/components/PropertyTitleBlock";
import PropertyAddressBlock from "@/components/PropertyAddressBlock";
import PropertyQuickActions from "@/components/PropertyQuickActions";
import PropertyMainCTAButtons from "@/components/PropertyMainCTAButtons";
import PropertyKeyFeatures from "@/components/PropertyKeyFeatures";
import PropertyDescription from "@/components/PropertyDescription";
import PropertyFullDetails from "@/components/PropertyFullDetails";
import PropertyInfrastructure from "@/components/PropertyInfrastructure";
import PropertyMap from "@/components/PropertyMap";
import PropertySimilarObjects from "@/components/PropertySimilarObjects";

interface Photo {
  id: string;
  url: string;
  alt?: string;
}

interface Feature {
  label: string;
  value: string;
}

interface Section {
  title: string;
  parameters: { label: string; value: string }[];
}

interface InfrastructureItem {
  type: string;
  icon: string;
  name: string;
}

interface SimilarObject {
  id: string;
  image: string;
  price: number;
  area?: number;
  floor?: number;
  totalFloors?: number;
  rooms?: number;
  district?: string;
  address?: string;
}

interface PropertyMainBlockProps {
  // Gallery
  photos: Photo[];
  propertyTitle: string;

  // Price & Status
  price: number;
  pricePerSquareMeter: number;
  status?: "good_price" | "new" | "price_drop" | "verified" | null;

  // Title
  rooms: number;
  propertyType: "квартира" | "апартаменты" | "дом" | "комната";
  squareMeters: number;
  floor: number;
  totalFloors: number;
  buildingName?: string;
  city?: string;

  // Address
  address: string;
  district?: string;
  nearestMetro?: { name: string; walkTime: number } | null;
  addressCity: string;

  // Quick Actions
  propertyId: string;
  propertyForActions: {
    id: string;
    title: string;
    price: number;
    image: string;
    area: number;
    rooms: number;
    floor: number;
    address: string;
    type: string;
    pricePerMeter?: number;
  };

  // CTA
  phone: string;
  agentName: string;
  hasSecurity?: boolean;
  inRegistry?: boolean;
  ctaPropertyTitle: string;

  // Key Features
  keyFeatures: Feature[];

  // Description
  description: string;

  // Full Details
  fullDetails: Section[];

  // Infrastructure
  infrastructure: InfrastructureItem[];

  // Map
  latitude: number;
  longitude: number;
  mapAddress: string;
  mapCity: string;

  // Similar Objects
  similarObjects: SimilarObject[];

  className?: string;
}

const PropertyMainBlock = ({
  photos,
  propertyTitle,
  price,
  pricePerSquareMeter,
  status,
  rooms,
  propertyType,
  squareMeters,
  floor,
  totalFloors,
  buildingName,
  city,
  address,
  district,
  nearestMetro,
  addressCity,
  propertyId,
  propertyForActions,
  phone,
  agentName,
  hasSecurity,
  inRegistry,
  ctaPropertyTitle,
  keyFeatures,
  description,
  fullDetails,
  infrastructure,
  latitude,
  longitude,
  mapAddress,
  mapCity,
  similarObjects,
  className,
}: PropertyMainBlockProps) => {
  return (
    <div className={cn("w-full", className)}>
      {/* Mobile: Single Column Layout */}
      <div className="lg:hidden space-y-0">
        {/* 1. Gallery - Full Width */}
        <div className="mb-0">
          <PropertyMediaGallery photos={photos} propertyTitle={propertyTitle} />
        </div>

        {/* 2. Price & Status */}
        <PropertyPriceStatusBlock
          price={price}
          pricePerSquareMeter={pricePerSquareMeter}
          status={status}
        />

        {/* 3. Title */}
        <PropertyTitleBlock
          rooms={rooms}
          type={propertyType}
          squareMeters={squareMeters}
          floor={floor}
          totalFloors={totalFloors}
          buildingName={buildingName}
          city={city}
        />

        {/* 4. Address */}
        <PropertyAddressBlock
          address={address}
          district={district}
          nearestMetro={nearestMetro}
          city={addressCity}
        />

        {/* 5. Quick Actions */}
        <PropertyQuickActions
          propertyId={propertyId}
          property={propertyForActions}
        />

        {/* 6. Main CTA Buttons */}
        <PropertyMainCTAButtons
          phone={phone}
          agentName={agentName}
          hasSecurity={hasSecurity}
          inRegistry={inRegistry}
          propertyTitle={ctaPropertyTitle}
        />

        {/* 7. Key Features */}
        <PropertyKeyFeatures features={keyFeatures} />

        {/* 8. Description */}
        <PropertyDescription description={description} />

        {/* 9. Full Details */}
        <PropertyFullDetails sections={fullDetails} />

        {/* 10. Infrastructure */}
        <PropertyInfrastructure infrastructure={infrastructure} />

        {/* 11. Map */}
        <PropertyMap
          latitude={latitude}
          longitude={longitude}
          address={mapAddress}
          city={mapCity}
        />

        {/* 12. Similar Objects */}
        <PropertySimilarObjects similar={similarObjects} />
      </div>

      {/* Desktop: Two Column Layout */}
      <div className="hidden lg:grid lg:grid-cols-[1fr_400px] lg:gap-8">
        {/* Left Column: Gallery + Main Content */}
        <div className="space-y-0">
          {/* 1. Gallery */}
          <div className="mb-6">
            <PropertyMediaGallery photos={photos} propertyTitle={propertyTitle} />
          </div>

          {/* 2. Title */}
          <PropertyTitleBlock
            rooms={rooms}
            type={propertyType}
            squareMeters={squareMeters}
            floor={floor}
            totalFloors={totalFloors}
            buildingName={buildingName}
            city={city}
          />

          {/* 3. Address */}
          <PropertyAddressBlock
            address={address}
            district={district}
            nearestMetro={nearestMetro}
            city={addressCity}
          />

          {/* 4. Quick Actions */}
          <PropertyQuickActions
            propertyId={propertyId}
            property={propertyForActions}
          />

          {/* 5. Key Features */}
          <PropertyKeyFeatures features={keyFeatures} />

          {/* 6. Description */}
          <PropertyDescription description={description} />

          {/* 7. Full Details */}
          <PropertyFullDetails sections={fullDetails} />

          {/* 8. Infrastructure */}
          <PropertyInfrastructure infrastructure={infrastructure} />

          {/* 9. Map */}
          <PropertyMap
            latitude={latitude}
            longitude={longitude}
            address={mapAddress}
            city={mapCity}
          />

          {/* 10. Similar Objects */}
          <PropertySimilarObjects similar={similarObjects} />
        </div>

        {/* Right Column: Sticky Panel */}
        <div className="lg:sticky lg:top-6 lg:h-fit">
          <div className="bg-white rounded-xl border border-[#EEEEEE] shadow-sm overflow-hidden">
            {/* Price & Status */}
            <PropertyPriceStatusBlock
              price={price}
              pricePerSquareMeter={pricePerSquareMeter}
              status={status}
            />

            {/* Main CTA Buttons */}
            <PropertyMainCTAButtons
              phone={phone}
              agentName={agentName}
              hasSecurity={hasSecurity}
              inRegistry={inRegistry}
              propertyTitle={ctaPropertyTitle}
            />
          </div>
        </div>
      </div>
    </div>
  );
};

export default PropertyMainBlock;

