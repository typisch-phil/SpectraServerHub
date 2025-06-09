import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import Nav from "@/components/ui/nav";
import Footer from "@/components/ui/footer";

export default function Impressum() {
  return (
    <div className="min-h-screen bg-background">
      <Nav />
      
      <div className="pt-20 pb-16">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h1 className="text-4xl md:text-5xl font-bold text-foreground mb-4">Impressum</h1>
            <p className="text-xl text-muted-foreground">
              Angaben gemäß § 5 TMG
            </p>
          </div>

          <div className="space-y-8">
            <Card>
              <CardHeader>
                <CardTitle>Anbieter</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <h3 className="font-semibold text-lg mb-2">SpectraHost GmbH</h3>
                  <div className="text-gray-700 space-y-1">
                    <p>Mainzer Landstraße 123</p>
                    <p>60327 Frankfurt am Main</p>
                    <p>Deutschland</p>
                  </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
                  <div>
                    <h4 className="font-semibold mb-2">Kontakt</h4>
                    <div className="text-gray-700 space-y-1">
                      <p>Telefon: +49 (0) 69 123 456 789</p>
                      <p>E-Mail: info@spectrahost.de</p>
                      <p>Web: www.spectrahost.de</p>
                    </div>
                  </div>
                  
                  <div>
                    <h4 className="font-semibold mb-2">Geschäftsführung</h4>
                    <div className="text-gray-700 space-y-1">
                      <p>Max Mustermann</p>
                      <p>Sarah Schmidt</p>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Registereintrag</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <h4 className="font-semibold mb-2">Handelsregister</h4>
                    <div className="text-gray-700 space-y-1">
                      <p>Eintragung im Handelsregister</p>
                      <p>Registergericht: Amtsgericht Frankfurt am Main</p>
                      <p>Registernummer: HRB 123456</p>
                    </div>
                  </div>
                  
                  <div>
                    <h4 className="font-semibold mb-2">Umsatzsteuer</h4>
                    <div className="text-gray-700 space-y-1">
                      <p>Umsatzsteuer-Identifikationsnummer</p>
                      <p>gemäß § 27 a Umsatzsteuergesetz:</p>
                      <p>DE 123 456 789</p>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Aufsichtsbehörde</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-gray-700 space-y-1">
                  <p>Bundesnetzagentur für Elektrizität, Gas, Telekommunikation, Post und Eisenbahnen</p>
                  <p>Tulpenfeld 4</p>
                  <p>53113 Bonn</p>
                  <p>Deutschland</p>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Haftungsausschluss</CardTitle>
              </CardHeader>
              <CardContent className="space-y-6">
                <div>
                  <h4 className="font-semibold mb-2">Haftung für Inhalte</h4>
                  <p className="text-gray-700 leading-relaxed">
                    Als Diensteanbieter sind wir gemäß § 7 Abs.1 TMG für eigene Inhalte auf diesen Seiten 
                    nach den allgemeinen Gesetzen verantwortlich. Nach §§ 8 bis 10 TMG sind wir als 
                    Diensteanbieter jedoch nicht unter der Verpflichtung, übermittelte oder gespeicherte 
                    fremde Informationen zu überwachen oder nach Umständen zu forschen, die auf eine 
                    rechtswidrige Tätigkeit hinweisen.
                  </p>
                </div>

                <div>
                  <h4 className="font-semibold mb-2">Haftung für Links</h4>
                  <p className="text-gray-700 leading-relaxed">
                    Unser Angebot enthält Links zu externen Websites Dritter, auf deren Inhalte wir keinen 
                    Einfluss haben. Deshalb können wir für diese fremden Inhalte auch keine Gewähr übernehmen. 
                    Für die Inhalte der verlinkten Seiten ist stets der jeweilige Anbieter oder Betreiber 
                    der Seiten verantwortlich.
                  </p>
                </div>

                <div>
                  <h4 className="font-semibold mb-2">Urheberrecht</h4>
                  <p className="text-gray-700 leading-relaxed">
                    Die durch die Seitenbetreiber erstellten Inhalte und Werke auf diesen Seiten unterliegen 
                    dem deutschen Urheberrecht. Die Vervielfältigung, Bearbeitung, Verbreitung und jede Art 
                    der Verwertung außerhalb der Grenzen des Urheberrechtes bedürfen der schriftlichen 
                    Zustimmung des jeweiligen Autors bzw. Erstellers.
                  </p>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Streitschlichtung</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-gray-700 leading-relaxed">
                  Die Europäische Kommission stellt eine Plattform zur Online-Streitbeilegung (OS) bereit: 
                  <a href="https://ec.europa.eu/consumers/odr/" className="text-primary hover:underline ml-1" target="_blank" rel="noopener noreferrer">
                    https://ec.europa.eu/consumers/odr/
                  </a>
                  <br /><br />
                  Wir sind nicht bereit oder verpflichtet, an Streitbeilegungsverfahren vor einer 
                  Verbraucherschlichtungsstelle teilzunehmen.
                </p>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>

      <Footer />
    </div>
  );
}