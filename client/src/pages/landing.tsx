import Nav from "@/components/ui/nav";
import Hero from "@/components/ui/hero";
import Services from "@/components/ui/services";
import Pricing from "@/components/ui/pricing";
import Features from "@/components/ui/features";
import Footer from "@/components/ui/footer";

export default function Landing() {
  return (
    <div className="min-h-screen">
      <Nav />
      <Hero />
      <Services />
      <Pricing />
      <Features />
      <Footer />
    </div>
  );
}
