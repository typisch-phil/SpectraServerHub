import { useEffect } from "react";
import { useAuth } from "@/hooks/useAuth";
import { useToast } from "@/hooks/use-toast";
import { useQuery } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import DashboardNav from "@/components/ui/dashboard-nav";
import { 
  Server, 
  Globe, 
  Gamepad2, 
  Activity,
  CreditCard,
  Users,
  TrendingUp
} from "lucide-react";

export default function Dashboard() {
  const { user, isAuthenticated, isLoading } = useAuth();
  const { toast } = useToast();

  const { data: userServices } = useQuery({
    queryKey: ["/api/user/services"],
    enabled: isAuthenticated,
  });

  const { data: orders } = useQuery({
    queryKey: ["/api/user/orders"],
    enabled: isAuthenticated,
  });

  useEffect(() => {
    if (!isLoading && !isAuthenticated) {
      toast({
        title: "Nicht angemeldet",
        description: "Sie müssen sich anmelden, um das Dashboard zu verwenden.",
        variant: "destructive",
      });
      setTimeout(() => {
        window.location.href = "/login";
      }, 500);
      return;
    }
  }, [isAuthenticated, isLoading, toast]);

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background">
        <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-primary"></div>
      </div>
    );
  }

  if (!isAuthenticated || !user) {
    return null;
  }

  const getServiceIcon = (type: string) => {
    switch (type) {
      case 'webspace': return Globe;
      case 'vserver': return Server;
      case 'gameserver': return Gamepad2;
      default: return Server;
    }
  };

  const activeServices = Array.isArray(userServices) ? userServices.length : 0;
  const monthlyCost = Array.isArray(userServices) ? userServices.reduce((total: number, service: any) => {
    return total + parseFloat(service.price || '0');
  }, 0) : 0;

  return (
    <div className="min-h-screen bg-background">
      <DashboardNav />
      
      <div className="pt-20 pb-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="mb-8">
            <h1 className="text-3xl font-bold text-foreground">Dashboard</h1>
            <p className="text-muted-foreground mt-2">
              Verwalten Sie Ihre Hosting-Services und überwachen Sie Ihre Infrastruktur
            </p>
          </div>

          {/* Overview Cards */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <Card className="bg-gradient-to-br from-primary/5 to-primary/10">
              <CardHeader className="pb-3">
                <CardTitle className="text-lg font-semibold text-foreground flex items-center">
                  <Activity className="w-5 h-5 mr-2 text-primary" />
                  Account Overview
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-2">
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Active Services:</span>
                    <span className="font-medium text-foreground">{activeServices}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Monthly Cost:</span>
                    <span className="font-medium text-foreground">€{monthlyCost.toFixed(2)}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Account Status:</span>
                    <Badge variant="default" className="bg-green-100 text-green-800">Active</Badge>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card className="bg-gradient-to-br from-green-500/5 to-green-500/10">
              <CardHeader className="pb-3">
                <CardTitle className="text-lg font-semibold text-foreground flex items-center">
                  <TrendingUp className="w-5 h-5 mr-2 text-green-500" />
                  Performance
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-2">
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Uptime:</span>
                    <span className="font-medium text-foreground">99.98%</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Response Time:</span>
                    <span className="font-medium text-foreground">245ms</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Status:</span>
                    <Badge variant="default" className="bg-green-100 text-green-800">Healthy</Badge>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card className="bg-gradient-to-br from-blue-500/5 to-blue-500/10">
              <CardHeader className="pb-3">
                <CardTitle className="text-lg font-semibold text-foreground flex items-center">
                  <CreditCard className="w-5 h-5 mr-2 text-blue-500" />
                  Billing
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-2">
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Next Bill:</span>
                    <span className="font-medium text-foreground">Jan 15, 2025</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Amount:</span>
                    <span className="font-medium text-foreground">€{monthlyCost.toFixed(2)}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Payment:</span>
                    <Badge variant="default" className="bg-blue-100 text-blue-800">Auto</Badge>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Services Section */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <Card>
              <CardHeader>
                <CardTitle className="text-foreground">Ihre Services</CardTitle>
              </CardHeader>
              <CardContent>
                {Array.isArray(userServices) && userServices.length > 0 ? (
                  <div className="space-y-4">
                    {userServices.map((service: any) => {
                      const IconComponent = getServiceIcon(service.type);
                      return (
                        <div key={service.id} className="flex items-center justify-between p-4 border border-border rounded-lg">
                          <div className="flex items-center">
                            <IconComponent className="w-8 h-8 text-primary mr-3" />
                            <div>
                              <h3 className="font-medium text-foreground">{service.name}</h3>
                              <p className="text-sm text-muted-foreground">{service.type}</p>
                            </div>
                          </div>
                          <div className="text-right">
                            <Badge variant={service.status === 'active' ? 'default' : 'secondary'}>
                              {service.status}
                            </Badge>
                            <p className="text-sm text-muted-foreground mt-1">€{service.price}/Monat</p>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                ) : (
                  <div className="text-center py-8">
                    <Users className="w-12 h-12 text-muted-foreground mx-auto mb-4" />
                    <h3 className="text-lg font-medium text-foreground mb-2">Keine Services</h3>
                    <p className="text-muted-foreground mb-4">Sie haben noch keine Hosting-Services bestellt.</p>
                    <Button asChild>
                      <a href="/order">Services bestellen</a>
                    </Button>
                  </div>
                )}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className="text-foreground">Letzte Bestellungen</CardTitle>
              </CardHeader>
              <CardContent>
                {Array.isArray(orders) && orders.length > 0 ? (
                  <div className="space-y-4">
                    {orders.slice(0, 5).map((order: any) => (
                      <div key={order.id} className="flex items-center justify-between p-4 border border-border rounded-lg">
                        <div>
                          <h3 className="font-medium text-foreground">Bestellung #{order.id}</h3>
                          <p className="text-sm text-muted-foreground">
                            {new Date(order.createdAt).toLocaleDateString('de-DE')}
                          </p>
                        </div>
                        <div className="text-right">
                          <Badge variant={order.status === 'completed' ? 'default' : 'secondary'}>
                            {order.status}
                          </Badge>
                          <p className="text-sm text-muted-foreground mt-1">€{order.totalAmount}</p>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className="text-center py-8">
                    <CreditCard className="w-12 h-12 text-muted-foreground mx-auto mb-4" />
                    <h3 className="text-lg font-medium text-foreground mb-2">Keine Bestellungen</h3>
                    <p className="text-muted-foreground">Sie haben noch keine Bestellungen aufgegeben.</p>
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