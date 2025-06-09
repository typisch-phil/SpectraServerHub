import { Card, CardContent } from "@/components/ui/card";
import { Globe, Server, Link as LinkIcon, Gamepad2, Check } from "lucide-react";

const services = [
  {
    icon: Globe,
    title: "Webspace",
    description: "Zuverlässiges Webhosting mit SSD-Speicher und 99.9% Uptime-Garantie",
    features: ["Unlimited Traffic", "SSL-Zertifikate", "24/7 Support"],
    gradient: "from-primary/5 to-transparent",
    iconBg: "bg-primary/10",
    iconColor: "text-primary"
  },
  {
    icon: Server,
    title: "vServer",
    description: "Leistungsstarke virtuelle Server mit Root-Zugriff und voller Kontrolle",
    features: ["KVM Virtualisierung", "NVMe SSD Storage", "Snapshot-Backups"],
    gradient: "from-secondary/5 to-transparent",
    iconBg: "bg-secondary/10",
    iconColor: "text-secondary"
  },
  {
    icon: LinkIcon,
    title: "Domains",
    description: "Registrierung und Verwaltung von Domains aller gängigen Endungen",
    features: ["Über 500 TLDs", "DNS-Management", "Domain-Transfer"],
    gradient: "from-accent/5 to-transparent",
    iconBg: "bg-accent/10",
    iconColor: "text-accent"
  },
  {
    icon: Gamepad2,
    title: "GameServer",
    description: "Optimierte Gaming-Server für alle beliebten Spiele mit niedrigster Latenz",
    features: ["Minecraft, CS2, ARK", "DDoS-Schutz", "1-Click Installation"],
    gradient: "from-green-500/5 to-transparent",
    iconBg: "bg-green-500/10",
    iconColor: "text-green-500"
  }
];

export default function Services() {
  return (
    <section id="services" className="py-20 bg-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-16">
          <h2 className="text-4xl md:text-5xl font-bold text-gray-900 mb-4">Unsere Services</h2>
          <p className="text-xl text-gray-600 max-w-3xl mx-auto">
            Von Webhosting bis GameServer - wir bieten die komplette Palette professioneller Hosting-Lösungen
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          {services.map((service, index) => (
            <Card 
              key={index}
              className="group relative bg-gradient-to-br from-white to-gray-50 hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 border border-gray-100"
            >
              <div className={`absolute inset-0 bg-gradient-to-br ${service.gradient} rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300`}></div>
              <CardContent className="relative p-8">
                <div className={`w-16 h-16 ${service.iconBg} rounded-lg flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300`}>
                  <service.icon className={`text-2xl ${service.iconColor}`} />
                </div>
                <h3 className="text-xl font-bold text-gray-900 mb-4">{service.title}</h3>
                <p className="text-gray-600 mb-6">{service.description}</p>
                <ul className="space-y-2 text-sm text-gray-600">
                  {service.features.map((feature, featureIndex) => (
                    <li key={featureIndex} className="flex items-center">
                      <Check className="w-4 h-4 text-green-500 mr-2" />
                      {feature}
                    </li>
                  ))}
                </ul>
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    </section>
  );
}
