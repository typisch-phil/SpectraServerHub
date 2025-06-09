import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Check } from "lucide-react";
import { Link } from "wouter";

const plans = [
  {
    name: "Starter",
    price: "4.99",
    description: "Perfekt für kleine Projekte",
    features: [
      "5 GB SSD Speicher",
      "1 Domain inklusive",
      "SSL-Zertifikat",
      "Email-Support"
    ],
    popular: false
  },
  {
    name: "Pro",
    price: "14.99",
    description: "Ideal für wachsende Unternehmen",
    features: [
      "50 GB SSD Speicher",
      "5 Domains inklusive",
      "Premium SSL",
      "24/7 Support",
      "Daily Backups"
    ],
    popular: true
  },
  {
    name: "Enterprise",
    price: "49.99",
    description: "Für maximale Performance",
    features: [
      "200 GB NVMe SSD",
      "Unlimited Domains",
      "Wildcard SSL",
      "Priority Support",
      "Hourly Backups"
    ],
    popular: false
  }
];

export default function Pricing() {
  return (
    <section id="pricing" className="py-20 bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-16">
          <h2 className="text-4xl md:text-5xl font-bold text-gray-900 mb-4">Transparente Preise</h2>
          <p className="text-xl text-gray-600 max-w-3xl mx-auto">
            Keine versteckten Kosten, faire Preise und maximale Leistung für jeden Bedarf
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          {plans.map((plan, index) => (
            <Card 
              key={index}
              className={`relative hover:shadow-xl transition-shadow duration-300 ${
                plan.popular ? 'border-2 border-primary' : 'border border-gray-200'
              }`}
            >
              {plan.popular && (
                <div className="absolute -top-4 left-1/2 transform -translate-x-1/2">
                  <Badge className="bg-primary text-white px-4 py-2">Beliebt</Badge>
                </div>
              )}
              <CardContent className="p-8 text-center">
                <h3 className="text-2xl font-bold text-gray-900 mb-2">{plan.name}</h3>
                <p className="text-gray-600 mb-6">{plan.description}</p>
                <div className="mb-8">
                  <span className="text-4xl font-bold text-primary">€{plan.price}</span>
                  <span className="text-gray-600">/Monat</span>
                </div>
                <ul className="space-y-4 text-left mb-8">
                  {plan.features.map((feature, featureIndex) => (
                    <li key={featureIndex} className="flex items-center">
                      <Check className="w-5 h-5 text-green-500 mr-3" />
                      {feature}
                    </li>
                  ))}
                </ul>
                <Link href="/order">
                  <Button 
                    className={`w-full ${plan.popular ? 'bg-primary hover:bg-primary/90' : ''}`}
                    variant={plan.popular ? "default" : "outline"}
                  >
                    Plan wählen
                  </Button>
                </Link>
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    </section>
  );
}
