import { Button } from "@/components/ui/button";
import { Rocket, Play } from "lucide-react";
import { Link } from "wouter";

export default function Hero() {
  return (
    <section className="relative pt-20 pb-20 bg-gradient-to-br from-background via-background/80 to-background overflow-hidden">
      <div className="absolute inset-0 bg-gradient-to-r from-primary/20 to-accent/20"></div>
      
      <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center">
          <h1 className="text-5xl md:text-7xl font-bold text-foreground mb-6 animate-fade-in">
            Premium{" "}
            <span className="text-transparent bg-clip-text bg-gradient-to-r from-primary to-accent">
              Server Hosting
            </span>
          </h1>
          <p className="text-xl md:text-2xl text-muted-foreground mb-8 max-w-3xl mx-auto animate-slide-up">
            Hochperformante Server, zuverlässige Infrastruktur und erstklassiger Support für deine digitalen Projekte. 
            Seit 2015 vertrauen über 50.000 Kunden auf unsere bewährte Hosting-Lösung.
          </p>
          <div className="flex flex-wrap justify-center gap-8 mb-8 text-muted-foreground animate-fade-in">
            <div className="flex items-center">
              <div className="w-2 h-2 bg-green-400 rounded-full mr-2"></div>
              <span>99.98% Uptime</span>
            </div>
            <div className="flex items-center">
              <div className="w-2 h-2 bg-blue-400 rounded-full mr-2"></div>
              <span>24/7 Deutscher Support</span>
            </div>
            <div className="flex items-center">
              <div className="w-2 h-2 bg-purple-400 rounded-full mr-2"></div>
              <span>ISO 27001 Zertifiziert</span>
            </div>
          </div>
          <div className="flex flex-col sm:flex-row gap-4 justify-center animate-slide-up">
            <Link href="/order">
              <Button size="lg" className="text-lg font-semibold hover:scale-105 transition-transform duration-300">
                <Rocket className="w-5 h-5 mr-2" />
                Jetzt starten
              </Button>
            </Link>
            <Button 
              variant="outline" 
              size="lg"
              className="text-lg font-semibold transition-all duration-300"
            >
              <Play className="w-5 h-5 mr-2" />
              Demo ansehen
            </Button>
          </div>
        </div>
        
        {/* Floating Elements */}
        <div className="absolute top-1/4 left-10 w-20 h-20 bg-primary/30 rounded-full animate-float"></div>
        <div 
          className="absolute bottom-1/4 right-10 w-16 h-16 bg-accent/30 rounded-full animate-float"
          style={{ animationDelay: '1s' }}
        ></div>
      </div>
    </section>
  );
}
