import { Facebook, Twitter, Linkedin } from "lucide-react";

export default function Footer() {
  return (
    <footer id="contact" className="bg-slate-900 text-white py-16">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
          <div>
            <h3 className="text-2xl font-bold mb-4">SpectraHost</h3>
            <p className="text-gray-400 mb-4">Premium Server Hosting Solutions für moderne Anforderungen</p>
            <div className="flex space-x-4">
              <a href="#" className="text-gray-400 hover:text-white transition-colors duration-200">
                <Twitter className="w-5 h-5" />
              </a>
              <a href="#" className="text-gray-400 hover:text-white transition-colors duration-200">
                <Facebook className="w-5 h-5" />
              </a>
              <a href="#" className="text-gray-400 hover:text-white transition-colors duration-200">
                <Linkedin className="w-5 h-5" />
              </a>
            </div>
          </div>
          
          <div>
            <h4 className="text-lg font-semibold mb-4">Services</h4>
            <ul className="space-y-2 text-gray-400">
              <li><a href="#" className="hover:text-white transition-colors duration-200">Webhosting</a></li>
              <li><a href="#" className="hover:text-white transition-colors duration-200">vServer</a></li>
              <li><a href="#" className="hover:text-white transition-colors duration-200">Domains</a></li>
              <li><a href="#" className="hover:text-white transition-colors duration-200">GameServer</a></li>
            </ul>
          </div>
          
          <div>
            <h4 className="text-lg font-semibold mb-4">Support</h4>
            <ul className="space-y-2 text-gray-400">
              <li><a href="#" className="hover:text-white transition-colors duration-200">Hilfe-Center</a></li>
              <li><a href="#" className="hover:text-white transition-colors duration-200">Dokumentation</a></li>
              <li><a href="#" className="hover:text-white transition-colors duration-200">Status</a></li>
              <li><a href="#" className="hover:text-white transition-colors duration-200">Kontakt</a></li>
            </ul>
          </div>
          
          <div>
            <h4 className="text-lg font-semibold mb-4">Unternehmen</h4>
            <ul className="space-y-2 text-gray-400">
              <li><a href="#" className="hover:text-white transition-colors duration-200">Über uns</a></li>
              <li><a href="#" className="hover:text-white transition-colors duration-200">Karriere</a></li>
              <li><a href="#" className="hover:text-white transition-colors duration-200">Datenschutz</a></li>
              <li><a href="#" className="hover:text-white transition-colors duration-200">AGB</a></li>
            </ul>
          </div>
        </div>
        
        <div className="border-t border-gray-800 mt-12 pt-8 text-center text-gray-400">
          <p>&copy; 2024 SpectraHost. Alle Rechte vorbehalten.</p>
        </div>
      </div>
    </footer>
  );
}
