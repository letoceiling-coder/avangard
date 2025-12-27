import { Toaster } from "@/components/ui/toaster";
import { Toaster as Sonner } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { BrowserRouter, Routes, Route } from "react-router-dom";
import Index from "./pages/Index";
import ObjectDetail from "./pages/ObjectDetail";
import NotFound from "./pages/NotFound";
import RegionSelect from "./pages/RegionSelect";
import Catalog from "./pages/Catalog";
import PropertyDetail from "./pages/PropertyDetail";
import Favorites from "./pages/Favorites";
import Contacts from "./pages/Contacts";
import About from "./pages/About";
import Developers from "./pages/Developers";
import ResidentialComplex from "./pages/ResidentialComplex";
import ComplexDetail from "./pages/ComplexDetail";
import Compare from "./pages/Compare";
import Auth from "./pages/Auth";
import Profile from "./pages/Profile";
import PrivacyPolicy from "./pages/PrivacyPolicy";
import TermsOfService from "./pages/TermsOfService";
import AgentDashboard from "./pages/AgentDashboard";
import News from "./pages/News";
import NewsDetail from "./pages/NewsDetail";
import CompareBar from "./components/CompareBar";
import MobileBottomNav from "./components/MobileBottomNav";
import CookieConsent from "./components/CookieConsent";
import CityDetectionModal from "./components/CityDetectionModal";
import { ComparisonProvider } from "./hooks/useComparison";
import { AuthProvider } from "./hooks/useAuth";
import { RegionProvider } from "./hooks/useRegion";

const queryClient = new QueryClient();

const App = () => (
  <QueryClientProvider client={queryClient}>
    <TooltipProvider>
      <AuthProvider>
        <RegionProvider>
          <ComparisonProvider>
            <Toaster />
            <Sonner />
            <BrowserRouter>
              <Routes>
                <Route path="/region-select" element={<RegionSelect />} />
                <Route path="/" element={<Index />} />
                <Route path="/catalog" element={<Catalog />} />
                <Route path="/objects/:id" element={<ObjectDetail />} />
                <Route path="/property/:id" element={<PropertyDetail />} />
                <Route path="/favorites" element={<Favorites />} />
                <Route path="/contacts" element={<Contacts />} />
                <Route path="/about" element={<About />} />
                <Route path="/developers" element={<Developers />} />
                <Route path="/residential-complex" element={<ResidentialComplex />} />
                <Route path="/complex/:id" element={<ComplexDetail />} />
                <Route path="/buildings/:id" element={<ComplexDetail />} />
                <Route path="/compare" element={<Compare />} />
                <Route path="/auth" element={<Auth />} />
                <Route path="/profile" element={<Profile />} />
                <Route path="/privacy" element={<PrivacyPolicy />} />
                <Route path="/terms" element={<TermsOfService />} />
                <Route path="/agent" element={<AgentDashboard />} />
                <Route path="/news" element={<News />} />
                <Route path="/news/:id" element={<NewsDetail />} />
                <Route path="*" element={<NotFound />} />
              </Routes>
              <CompareBar />
              <MobileBottomNav />
              <CookieConsent />
              <CityDetectionModal />
            </BrowserRouter>
          </ComparisonProvider>
        </RegionProvider>
      </AuthProvider>
    </TooltipProvider>
  </QueryClientProvider>
);

export default App;
