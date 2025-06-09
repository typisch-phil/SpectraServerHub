import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";

const faqs = [
  {
    question: "Wie schnell ist mein Server nach der Bestellung verfügbar?",
    answer: "Webspace und vServer werden automatisch innerhalb von 5-10 Minuten nach Zahlungseingang bereitgestellt. GameServer sind sofort nach der Bestellung einsatzbereit. Bei Domains kann die Aktivierung je nach TLD zwischen 1-24 Stunden dauern."
  },
  {
    question: "Welche Zahlungsmethoden werden akzeptiert?",
    answer: "Wir akzeptieren alle gängigen Zahlungsmethoden: SEPA-Lastschrift, Kreditkarte (Visa, MasterCard), PayPal, Sofortüberweisung und auf Rechnung (bei Geschäftskunden). Alle Zahlungen werden über unseren zertifizierten Partner Mollie sicher abgewickelt."
  },
  {
    question: "Gibt es eine Geld-zurück-Garantie?",
    answer: "Ja, wir bieten eine 30-Tage Geld-zurück-Garantie auf alle Hosting-Pakete. Sollten Sie nicht zufrieden sein, erstatten wir Ihnen den vollen Betrag ohne Wenn und Aber zurück."
  },
  {
    question: "Kann ich mein Hosting-Paket jederzeit upgraden?",
    answer: "Absolut! Sie können Ihr Paket jederzeit über das Kundenpanel upgraden. Das Upgrade erfolgt sofort und Sie zahlen nur die Differenz für den restlichen Zeitraum. Downgrades sind zum nächsten Abrechnungszeitraum möglich."
  },
  {
    question: "Wo befinden sich die Server?",
    answer: "Alle unsere Server stehen in unserem eigenen Rechenzentrum in Frankfurt am Main, Deutschland. Das Rechenzentrum ist ISO 27001 und TÜV zertifiziert und bietet redundante Stromversorgung, Klimatisierung und 24/7 physische Sicherheit."
  },
  {
    question: "Wie funktioniert der Support?",
    answer: "Unser deutschsprachiges Support-Team ist 24/7 für Sie da. Sie erreichen uns per Telefon, E-Mail oder über das Ticket-System im Kundenpanel. Über 95% aller Anfragen werden innerhalb von 30 Minuten bearbeitet."
  },
  {
    question: "Sind kostenlose SSL-Zertifikate enthalten?",
    answer: "Ja, alle Hosting-Pakete enthalten kostenlose Let's Encrypt SSL-Zertifikate mit automatischer Erneuerung. Für Business-Kunden bieten wir auch Extended Validation (EV) Zertifikate gegen Aufpreis an."
  },
  {
    question: "Kann ich eigene Software installieren?",
    answer: "Bei vServern haben Sie vollen Root-Zugriff und können jede beliebige Software installieren. Bei Webspace-Paketen unterstützen wir die gängigsten CMS wie WordPress, Joomla, Drupal sowie PHP, MySQL, Node.js und Python Anwendungen."
  }
];

export default function FAQ() {
  return (
    <section className="py-20 bg-white">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-16">
          <h2 className="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
            Häufig gestellte Fragen
          </h2>
          <p className="text-xl text-gray-600">
            Hier finden Sie Antworten auf die wichtigsten Fragen rund um unsere Hosting-Services
          </p>
        </div>

        <Accordion type="single" collapsible className="space-y-4">
          {faqs.map((faq, index) => (
            <AccordionItem 
              key={index} 
              value={`item-${index}`}
              className="border border-gray-200 rounded-lg px-6 hover:shadow-md transition-shadow duration-200"
            >
              <AccordionTrigger className="text-left font-semibold text-gray-900 hover:text-primary">
                {faq.question}
              </AccordionTrigger>
              <AccordionContent className="text-gray-700 leading-relaxed">
                {faq.answer}
              </AccordionContent>
            </AccordionItem>
          ))}
        </Accordion>

        <div className="text-center mt-12">
          <p className="text-gray-600 mb-4">
            Haben Sie weitere Fragen? Unser Support-Team hilft Ihnen gerne weiter.
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="tel:+4969123456789" className="text-primary font-semibold hover:underline">
              📞 +49 (0) 69 123 456 789
            </a>
            <a href="mailto:support@spectrahost.de" className="text-primary font-semibold hover:underline">
              ✉️ support@spectrahost.de
            </a>
          </div>
        </div>
      </div>
    </section>
  );
}