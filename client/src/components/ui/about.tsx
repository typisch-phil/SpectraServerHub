import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { MapPin, Phone, Mail, Clock, Users, Building2 } from "lucide-react";
import { Link } from "wouter";

export default function About() {
  return (
    <section id="about" className="py-20 bg-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
          <div>
            <h2 className="text-4xl font-bold text-gray-900 mb-6">Über SpectraHost</h2>
            <div className="space-y-6 text-lg text-gray-700">
              <p>
                Seit 2015 sind wir Ihr verlässlicher Partner für professionelle Hosting-Lösungen. 
                Mit über 50.000 zufriedenen Kunden haben wir uns als einer der führenden deutschen 
                Hosting-Anbieter etabliert.
              </p>
              <p>
                Unser Rechenzentrum in Frankfurt am Main erfüllt höchste Sicherheitsstandards und 
                ist ISO 27001 zertifiziert. Wir setzen ausschließlich auf modernste Hardware von 
                Dell und Intel, um Ihnen maximale Performance zu garantieren.
              </p>
              <p>
                Unser deutschsprachiges Support-Team steht Ihnen 24/7 zur Verfügung und löst 
                über 95% aller Anfragen innerhalb von 30 Minuten.
              </p>
            </div>
            
            <div className="mt-8 grid grid-cols-2 gap-6">
              <div className="flex items-center">
                <Users className="w-6 h-6 text-primary mr-3" />
                <div>
                  <div className="font-semibold text-gray-900">50.000+</div>
                  <div className="text-gray-600 text-sm">Aktive Kunden</div>
                </div>
              </div>
              <div className="flex items-center">
                <Building2 className="w-6 h-6 text-primary mr-3" />
                <div>
                  <div className="font-semibold text-gray-900">Seit 2015</div>
                  <div className="text-gray-600 text-sm">Am Markt</div>
                </div>
              </div>
            </div>
          </div>

          <div className="space-y-6">
            <Card className="bg-gradient-to-br from-primary/5 to-primary/10 border-primary/20">
              <CardContent className="p-6">
                <h3 className="text-xl font-semibold text-gray-900 mb-4">Rechenzentrum Frankfurt</h3>
                <div className="space-y-3 text-gray-700">
                  <div className="flex items-center">
                    <MapPin className="w-5 h-5 text-primary mr-3 flex-shrink-0" />
                    <span>Rechenzentrum Frankfurt am Main, Deutschland</span>
                  </div>
                  <div className="flex items-center">
                    <Clock className="w-5 h-5 text-primary mr-3 flex-shrink-0" />
                    <span>99.98% Uptime SLA mit 24/7 Monitoring</span>
                  </div>
                  <div className="flex items-center">
                    <Building2 className="w-5 h-5 text-primary mr-3 flex-shrink-0" />
                    <span>ISO 27001 & TÜV zertifizierte Sicherheit</span>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card className="bg-gradient-to-br from-green-500/5 to-green-500/10 border-green-500/20">
              <CardContent className="p-6">
                <h3 className="text-xl font-semibold text-gray-900 mb-4">Support & Service</h3>
                <div className="space-y-3 text-gray-700">
                  <div className="flex items-center">
                    <Phone className="w-5 h-5 text-green-500 mr-3 flex-shrink-0" />
                    <span>24/7 deutschsprachiger Telefon-Support</span>
                  </div>
                  <div className="flex items-center">
                    <Mail className="w-5 h-5 text-green-500 mr-3 flex-shrink-0" />
                    <span>Ticket-System mit max. 30 Min. Reaktionszeit</span>
                  </div>
                  <div className="flex items-center">
                    <Users className="w-5 h-5 text-green-500 mr-3 flex-shrink-0" />
                    <span>Persönlicher Ansprechpartner für Enterprise</span>
                  </div>
                </div>
              </CardContent>
            </Card>

            <div className="text-center">
              <Link href="/register">
                <Button size="lg" className="bg-primary hover:bg-primary/90">
                  Jetzt kostenfrei testen
                </Button>
              </Link>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}