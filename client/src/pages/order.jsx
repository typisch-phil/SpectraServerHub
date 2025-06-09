import { useState } from "react";
import { useAuth } from "@/hooks/useAuth";
import { useQuery, useMutation } from "@tanstack/react-query";
import { useToast } from "@/hooks/use-toast";
import { isUnauthorizedError } from "@/lib/authUtils";
import { apiRequest } from "@/lib/queryClient";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Link } from "wouter";
import { 
  Server, 
  Globe, 
  Gamepad2, 
  Link as LinkIcon,
  Check,
  ArrowLeft,
  CreditCard
} from "lucide-react";

export default function Order() {
  const { toast } = useToast();
  const { isAuthenticated, isLoading } = useAuth();
  const [selectedService, setSelectedService] = useState<any>(null);
  const [domain, setDomain] = useState("");

  const { data: services, isLoading: servicesLoading } = useQuery({
    queryKey: ["/api/services"],
  });

  const orderMutation = useMutation({
    mutationFn: async (orderData) => {
      const response = await apiRequest("POST", "/api/orders", orderData);
      return response.json();
    },
    onSuccess: (data) => {
      toast({
        title: "Order Created",
        description: "Redirecting to payment...",
      });
      // Redirect to Mollie checkout
      if (data.checkoutUrl) {
        window.location.href = data.checkoutUrl;
      }
    },
    onError: (error) => {
      if (isUnauthorizedError(error)) {
        toast({
          title: "Unauthorized",
          description: "You need to log in to place an order.",
          variant: "destructive",
        });
        setTimeout(() => {
          window.location.href = "/api/login";
        }, 500);
        return;
      }
      toast({
        title: "Order Failed",
        description: "Failed to create order. Please try again.",
        variant: "destructive",
      });
    },
  });

  const getServiceIcon = (type) => {
    switch (type) {
      case 'webspace': return Globe;
      case 'vserver': return Server;
      case 'gameserver': return Gamepad2;
      case 'domain': return LinkIcon;
      default: return Server;
    }
  };

  const handleOrder = () => {
    if (!selectedService) return;
    if (!isAuthenticated) {
      toast({
        title: "Login Required",
        description: "Please log in to place an order.",
        variant: "destructive",
      });
      window.location.href = "/api/login";
      return;
    }

    orderMutation.mutate({
      serviceId: selectedService.id,
      amount: selectedService.price,
      domain: domain || null,
    });
  };

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-primary"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Navigation */}
      <nav className="bg-white border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center">
              <Link href="/">
                <span className="text-2xl font-bold text-primary cursor-pointer">SpectraHost</span>
              </Link>
              <span className="ml-4 text-sm text-gray-500">Order Services</span>
            </div>
            <div className="flex items-center space-x-4">
              {isAuthenticated ? (
                <Link href="/dashboard">
                  <Button variant="outline" size="sm">Dashboard</Button>
                </Link>
              ) : (
                <Button 
                  variant="outline" 
                  size="sm"
                  onClick={() => window.location.href = "/api/login"}
                >
                  Login
                </Button>
              )}
            </div>
          </div>
        </div>
      </nav>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="flex items-center mb-8">
          <Link href="/">
            <Button variant="ghost" size="sm" className="mr-4">
              <ArrowLeft className="w-4 h-4 mr-2" />
              Back
            </Button>
          </Link>
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Order Services</h1>
            <p className="text-gray-600 mt-2">Choose the perfect hosting solution for your needs</p>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Services List */}
          <div className="lg:col-span-2">
            <h2 className="text-xl font-semibold text-gray-900 mb-6">Available Services</h2>
            
            {servicesLoading ? (
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {[...Array(4)].map((_, i) => (
                  <Card key={i} className="animate-pulse">
                    <CardContent className="p-6">
                      <div className="h-16 bg-gray-200 rounded-lg mb-4"></div>
                      <div className="h-4 bg-gray-200 rounded mb-2"></div>
                      <div className="h-4 bg-gray-200 rounded mb-4 w-3/4"></div>
                      <div className="h-10 bg-gray-200 rounded"></div>
                    </CardContent>
                  </Card>
                ))}
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {services?.map((service) => {
                  const IconComponent = getServiceIcon(service.type);
                  const isSelected = selectedService?.id === service.id;
                  
                  return (
                    <Card 
                      key={service.id} 
                      className={`cursor-pointer transition-all duration-200 hover:shadow-lg ${
                        isSelected ? 'ring-2 ring-primary shadow-lg' : ''
                      }`}
                      onClick={() => setSelectedService(service)}
                    >
                      <CardContent className="p-6">
                        <div className="flex items-center justify-between mb-4">
                          <div className="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                            <IconComponent className="w-6 h-6 text-primary" />
                          </div>
                          {isSelected && (
                            <div className="w-6 h-6 bg-primary rounded-full flex items-center justify-center">
                              <Check className="w-4 h-4 text-white" />
                            </div>
                          )}
                        </div>
                        <h3 className="text-lg font-semibold text-gray-900 mb-2">{service.name}</h3>
                        <p className="text-gray-600 text-sm mb-4">{service.description}</p>
                        
                        {service.features && (
                          <ul className="space-y-1 mb-4">
                            {service.features.slice(0, 3).map((feature, index) => (
                              <li key={index} className="flex items-center text-sm text-gray-600">
                                <Check className="w-3 h-3 text-green-500 mr-2" />
                                {feature}
                              </li>
                            ))}
                          </ul>
                        )}
                        
                        <div className="flex items-center justify-between">
                          <span className="text-2xl font-bold text-primary">
                            €{service.price}
                            <span className="text-sm text-gray-600 font-normal">/month</span>
                          </span>
                          <Badge variant={isSelected ? "default" : "outline"}>
                            {isSelected ? "Selected" : "Select"}
                          </Badge>
                        </div>
                      </CardContent>
                    </Card>
                  );
                })}
              </div>
            )}
          </div>

          {/* Order Summary */}
          <div className="lg:col-span-1">
            <Card className="sticky top-8">
              <CardHeader>
                <CardTitle>Order Summary</CardTitle>
              </CardHeader>
              <CardContent>
                {selectedService ? (
                  <div className="space-y-6">
                    <div>
                      <h4 className="font-semibold text-gray-900 mb-2">{selectedService.name}</h4>
                      <p className="text-gray-600 text-sm mb-4">{selectedService.description}</p>
                      
                      {(selectedService.type === 'webspace' || selectedService.type === 'domain') && (
                        <div className="space-y-2">
                          <Label htmlFor="domain">Domain Name</Label>
                          <Input
                            id="domain"
                            placeholder="example.com"
                            value={domain}
                            onChange={(e) => setDomain(e.target.value)}
                          />
                        </div>
                      )}
                    </div>
                    
                    <Separator />
                    
                    <div className="space-y-2">
                      <div className="flex justify-between">
                        <span className="text-gray-600">Service:</span>
                        <span className="font-medium">€{selectedService.price}/month</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-gray-600">Setup Fee:</span>
                        <span className="font-medium">€0.00</span>
                      </div>
                    </div>
                    
                    <Separator />
                    
                    <div className="flex justify-between text-lg font-semibold">
                      <span>Total:</span>
                      <span className="text-primary">€{selectedService.price}</span>
                    </div>
                    
                    <Button 
                      className="w-full" 
                      onClick={handleOrder}
                      disabled={orderMutation.isPending}
                    >
                      {orderMutation.isPending ? (
                        "Processing..."
                      ) : (
                        <>
                          <CreditCard className="w-4 h-4 mr-2" />
                          Order Now
                        </>
                      )}
                    </Button>
                    
                    <p className="text-xs text-gray-500 text-center">
                      Secure payment powered by Mollie
                    </p>
                  </div>
                ) : (
                  <div className="text-center py-8">
                    <Server className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                    <h4 className="font-medium text-gray-900 mb-2">Select a Service</h4>
                    <p className="text-gray-600 text-sm">Choose a hosting service to see the order summary</p>
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </div>
  );
}
