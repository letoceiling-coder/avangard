import SearchWidget from "./SearchWidget";

const HeroSearch = () => {
  return (
    <div id="hero-search" className="w-full max-w-screen-sm mx-auto px-4 -mt-6 sm:-mt-8 relative z-20">
      <div className="animate-fade-in" style={{ animationDelay: "80ms" }}>
        <SearchWidget variant="hero" />
      </div>
    </div>
  );
};

export default HeroSearch;

