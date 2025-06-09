import { TrendingUp, Users, Shield, Award } from "lucide-react";

const stats = [
  {
    icon: Users,
    number: "50,000+",
    label: "Zufriedene Kunden",
    description: "Vertrauen seit 2015 auf unsere Services"
  },
  {
    icon: TrendingUp,
    number: "99.98%",
    label: "Uptime Garantie",
    description: "Hochverfügbare Infrastruktur"
  },
  {
    icon: Shield,
    number: "24/7",
    label: "Deutscher Support",
    description: "Experten-Support rund um die Uhr"
  },
  {
    icon: Award,
    number: "ISO 27001",
    label: "Zertifiziert",
    description: "Höchste Sicherheitsstandards"
  }
];

export default function Stats() {
  return (
    <section className="py-16 bg-slate-900">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          {stats.map((stat, index) => (
            <div 
              key={index}
              className="text-center group"
            >
              <div className="inline-flex items-center justify-center w-16 h-16 bg-primary/10 rounded-full mb-4 group-hover:bg-primary/20 transition-colors duration-300">
                <stat.icon className="w-8 h-8 text-primary" />
              </div>
              <div className="text-3xl font-bold text-white mb-2">{stat.number}</div>
              <div className="text-lg font-semibold text-white mb-1">{stat.label}</div>
              <div className="text-gray-400 text-sm">{stat.description}</div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}