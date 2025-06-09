import { Shield, Zap, Headphones } from "lucide-react";

const features = [
  {
    icon: Shield,
    title: "99.9% Uptime Garantie",
    description: "Zuverlässige Infrastruktur mit redundanten Systemen und proaktivem Monitoring"
  },
  {
    icon: Zap,
    title: "Blitzschnelle Performance",
    description: "NVMe SSD-Speicher und optimierte Server für maximale Geschwindigkeit"
  },
  {
    icon: Headphones,
    title: "24/7 Expert Support",
    description: "Deutschsprachiger Support von echten Experten, nicht von Bots"
  }
];

export default function Features() {
  return (
    <section id="features" className="py-20 bg-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
          <div>
            <h2 className="text-4xl font-bold text-gray-900 mb-6">Warum SpectraHost?</h2>
            <p className="text-xl text-gray-600 mb-8">
              Modernste Technologie trifft auf jahrelange Erfahrung im Hosting-Bereich
            </p>
            
            <div className="space-y-6">
              {features.map((feature, index) => (
                <div key={index} className="flex items-start">
                  <div className={`w-12 h-12 rounded-lg flex items-center justify-center mr-4 flex-shrink-0 ${
                    index === 0 ? 'bg-primary/10' : 
                    index === 1 ? 'bg-secondary/10' : 
                    'bg-green-500/10'
                  }`}>
                    <feature.icon className={`w-6 h-6 ${
                      index === 0 ? 'text-primary' : 
                      index === 1 ? 'text-secondary' : 
                      'text-green-500'
                    }`} />
                  </div>
                  <div>
                    <h3 className="text-lg font-semibold text-gray-900 mb-2">{feature.title}</h3>
                    <p className="text-gray-600">{feature.description}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
          
          <div className="relative">
            <img 
              src="https://images.unsplash.com/photo-1558494949-ef010cbdcc31?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&h=600" 
              alt="Modern server datacenter" 
              className="rounded-xl shadow-2xl w-full h-auto" 
            />
            <div className="absolute inset-0 bg-gradient-to-r from-primary/10 to-transparent rounded-xl"></div>
          </div>
        </div>
      </div>
    </section>
  );
}
