import { Button } from "@/components/ui/button";
import { ThemeToggle } from "@/components/ui/theme-toggle";
import { Link } from "wouter";
import { useAuth } from "@/hooks/useAuth";

export default function DashboardNav() {
  const { user } = useAuth();

  const handleLogout = async () => {
    try {
      await fetch("/api/auth/logout", { method: "POST" });
      window.location.href = "/";
    } catch (error) {
      console.error("Logout failed:", error);
    }
  };

  return (
    <nav className="fixed top-0 w-full z-50 bg-background/90 backdrop-blur-md border-b border-border">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          <div className="flex items-center">
            <Link href="/">
              <span className="text-2xl font-bold text-primary cursor-pointer">SpectraHost</span>
            </Link>
            <div className="hidden md:block ml-10">
              <div className="flex items-baseline space-x-8">
                <Link href="/dashboard">
                  <span className="text-foreground font-medium">Dashboard</span>
                </Link>
                <Link href="/order">
                  <span className="text-foreground/70 hover:text-primary transition-colors duration-200 cursor-pointer">
                    Services bestellen
                  </span>
                </Link>
                <Link href="/contact">
                  <span className="text-foreground/70 hover:text-primary transition-colors duration-200 cursor-pointer">
                    Support
                  </span>
                </Link>
              </div>
            </div>
          </div>
          <div className="flex items-center space-x-4">
            <ThemeToggle />
            {user && (
              <span className="text-foreground/70 text-sm">
                Willkommen, {user.firstName || user.email}
              </span>
            )}
            <Button variant="outline" onClick={handleLogout}>
              Abmelden
            </Button>
          </div>
        </div>
      </div>
    </nav>
  );
}