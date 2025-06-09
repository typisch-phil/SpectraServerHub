import { Button } from "@/components/ui/button";
import { ThemeToggle } from "@/components/ui/theme-toggle";
import { Link } from "wouter";
import { useAuth } from "@/hooks/useAuth";

export default function Nav() {
  const { isAuthenticated, user } = useAuth();
  const scrollToSection = (sectionId) => {
    const element = document.getElementById(sectionId);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth' });
    }
  };

  return (
    <nav className="fixed top-0 w-full z-50 bg-background/90 backdrop-blur-md border-b border-border">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <span className="text-2xl font-bold text-primary">SpectraHost</span>
            </div>
            <div className="hidden md:block ml-10">
              <div className="flex items-baseline space-x-8">
                <button 
                  onClick={() => scrollToSection('services')}
                  className="text-foreground/70 hover:text-primary transition-colors duration-200"
                >
                  Services
                </button>
                <button 
                  onClick={() => scrollToSection('pricing')}
                  className="text-foreground/70 hover:text-primary transition-colors duration-200"
                >
                  Pricing
                </button>
                <button 
                  onClick={() => scrollToSection('features')}
                  className="text-foreground/70 hover:text-primary transition-colors duration-200"
                >
                  Features
                </button>
                <Link href="/contact">
                  <span className="text-foreground/70 hover:text-primary transition-colors duration-200 cursor-pointer">
                    Kontakt
                  </span>
                </Link>
              </div>
            </div>
          </div>
          <div className="flex items-center space-x-4">
            <ThemeToggle />
            {isAuthenticated ? (
              <>
                <Link href="/dashboard">
                  <Button variant="ghost">Dashboard</Button>
                </Link>
                <Button 
                  variant="outline" 
                  onClick={async () => {
                    await fetch("/api/auth/logout", { method: "POST" });
                    window.location.href = "/";
                  }}
                >
                  Abmelden
                </Button>
              </>
            ) : (
              <>
                <Link href="/login">
                  <Button variant="ghost">Anmelden</Button>
                </Link>
                <Link href="/register">
                  <Button>Registrieren</Button>
                </Link>
              </>
            )}
          </div>
        </div>
      </div>
    </nav>
  );
}
