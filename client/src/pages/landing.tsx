import Nav from "@/components/ui/nav";
import Hero from "@/components/ui/hero";
import Stats from "@/components/ui/stats";
import Services from "@/components/ui/services";
import Pricing from "@/components/ui/pricing";
import Features from "@/components/ui/features";
import About from "@/components/ui/about";
import Testimonials from "@/components/ui/testimonials";
import FAQ from "@/components/ui/faq";
import Footer from "@/components/ui/footer";

export default function Landing() {
  return (
    <div className="min-h-screen">
      <Nav />
      <Hero />
      <Stats />
      <Services />
      <About />
      <Pricing />
      <Features />
      <Testimonials />
      <FAQ />
      <Footer />
    </div>
  );
}
