import { Card, CardContent } from "@/components/ui/card";
import { Star, Quote } from "lucide-react";

const testimonials = [
  {
    name: "Marcus Weber",
    company: "TechStartup GmbH",
    role: "CTO",
    content: "SpectraHost hat unsere Erwartungen übertroffen. Die Performance ist ausgezeichnet und der Support reagiert blitzschnell auf unsere Anfragen.",
    rating: 5,
    avatar: "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face"
  },
  {
    name: "Sarah Müller", 
    company: "E-Commerce Solutions",
    role: "Geschäftsführerin",
    content: "Seit dem Wechsel zu SpectraHost läuft unser Online-Shop stabiler denn je. Die 99.98% Uptime sind keine leeren Versprechungen!",
    rating: 5,
    avatar: "https://images.unsplash.com/photo-1494790108755-2616b612b786?w=100&h=100&fit=crop&crop=face"
  },
  {
    name: "Daniel Koch",
    company: "Gaming Community",
    role: "Community Manager", 
    content: "Unsere Minecraft-Server laufen perfekt auf SpectraHost. Die Gaming-Server sind optimal konfiguriert und die Latenz ist minimal.",
    rating: 5,
    avatar: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&h=100&fit=crop&crop=face"
  }
];

export default function Testimonials() {
  return (
    <section className="py-20 bg-muted/30">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-16">
          <h2 className="text-4xl md:text-5xl font-bold text-foreground mb-4">Was unsere Kunden sagen</h2>
          <p className="text-xl text-muted-foreground max-w-3xl mx-auto">
            Über 50.000 zufriedene Kunden vertrauen auf unsere Hosting-Lösungen
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          {testimonials.map((testimonial, index) => (
            <Card key={index} className="relative bg-card hover:shadow-xl transition-shadow duration-300">
              <CardContent className="p-8">
                <div className="absolute top-4 right-4">
                  <Quote className="w-8 h-8 text-primary/20" />
                </div>
                
                <div className="flex items-center mb-4">
                  {[...Array(testimonial.rating)].map((_, i) => (
                    <Star key={i} className="w-5 h-5 text-yellow-400 fill-current" />
                  ))}
                </div>
                
                <p className="text-muted-foreground mb-6 text-lg leading-relaxed">
                  "{testimonial.content}"
                </p>
                
                <div className="flex items-center">
                  <img 
                    src={testimonial.avatar} 
                    alt={testimonial.name}
                    className="w-12 h-12 rounded-full mr-4 object-cover"
                  />
                  <div>
                    <div className="font-semibold text-foreground">{testimonial.name}</div>
                    <div className="text-muted-foreground text-sm">{testimonial.role}</div>
                    <div className="text-primary text-sm font-medium">{testimonial.company}</div>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    </section>
  );
}