import { useEffect } from "react";
import { useAuth } from "@/hooks/useAuth";
import { useToast } from "@/hooks/use-toast";
import { useQuery } from "@tanstack/react-query";
import { apiRequest } from "@/lib/queryClient";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Link } from "wouter";
import DashboardNav from "@/components/ui/dashboard-nav";
import { 
  Server, 
  Globe, 
  Gamepad2, 
  Link as LinkIcon,
  CreditCard,
  MessageSquare,
  Plus,
  Activity,
  Clock,
  CheckCircle,
  Settings
} from "lucide-react";

export default function Dashboard() {
  const { toast } = useToast();
  const { isAuthenticated, isLoading, user } = useAuth();

  const { data: userServices, isLoading: servicesLoading } = useQuery({
    queryKey: ["/api/user/services"],
    enabled: isAuthenticated,
  });

  const { data: orders, isLoading: ordersLoading } = useQuery({
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
      <div className="min-h-screen flex items-center justify-center">
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
      case 'domain': return LinkIcon;
      default: return Server;
    }
  };

  const activeServices = userServices?.length || 0;
  const monthlyCost = userServices?.reduce((total: number, service: any) => {
    return total + parseFloat(service.price || '0');
  }, 0) || 0;

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Navigation */}
      <nav className="bg-white border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center">
              <span className="text-2xl font-bold text-primary">SpectraHost</span>
              <span className="ml-4 text-sm text-gray-500">Dashboard</span>
            </div>
            <div className="flex items-center space-x-4">
              <span className="text-sm text-gray-600">
                Welcome, {user.firstName || user.email}
              </span>
              <Button
                variant="outline"
                size="sm"
                onClick={async () => {
                  try {
                    await apiRequest("POST", "/api/auth/logout", {});
                    window.location.href = "/";
                  } catch (error) {
                    console.error("Logout error:", error);
                    window.location.href = "/";
                  }
                }}
              >
                Abmelden
              </Button>
            </div>
          </div>
        </div>
      </nav>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900">Dashboard</h1>
          <p className="text-gray-600 mt-2">Manage your hosting services and account</p>
        </div>

        {/* Overview Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <Card className="bg-gradient-to-br from-primary/5 to-primary/10">
            <CardHeader className="pb-3">
              <CardTitle className="text-lg font-semibold text-gray-900 flex items-center">
                <Activity className="w-5 h-5 mr-2 text-primary" />
                Account Overview
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex justify-between">
                <span className="text-gray-600">Active Services:</span>
                <span className="font-semibold text-primary">{activeServices}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600">Monthly Cost:</span>
                <span className="font-semibold text-green-600">€{monthlyCost.toFixed(2)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600">Account Status:</span>
                <Badge variant="outline" className="text-green-600 border-green-600">Active</Badge>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gradient-to-br from-secondary/5 to-secondary/10">
            <CardHeader className="pb-3">
              <CardTitle className="text-lg font-semibold text-gray-900 flex items-center">
                <Plus className="w-5 h-5 mr-2 text-secondary" />
                Quick Actions
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <Link href="/order">
                <Button variant="ghost" className="w-full justify-start" size="sm">
                  <Plus className="w-4 h-4 mr-2" />
                  Order New Service
                </Button>
              </Link>
              <Button variant="ghost" className="w-full justify-start" size="sm">
                <CreditCard className="w-4 h-4 mr-2" />
                Manage Payments
              </Button>
              <Button variant="ghost" className="w-full justify-start" size="sm">
                <MessageSquare className="w-4 h-4 mr-2" />
                Contact Support
              </Button>
            </CardContent>
          </Card>

          <Card className="bg-gradient-to-br from-green-500/5 to-green-500/10">
            <CardHeader className="pb-3">
              <CardTitle className="text-lg font-semibold text-gray-900 flex items-center">
                <CheckCircle className="w-5 h-5 mr-2 text-green-500" />
                System Status
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-gray-600">All Systems:</span>
                <span className="flex items-center text-green-500">
                  <div className="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                  Operational
                </span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-gray-600">Uptime:</span>
                <span className="font-semibold text-green-500">99.98%</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-gray-600">Response Time:</span>
                <span className="font-semibold text-green-500">14ms</span>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Services List */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center justify-between">
              Your Services
              <Link href="/order">
                <Button size="sm">
                  <Plus className="w-4 h-4 mr-2" />
                  Add Service
                </Button>
              </Link>
            </CardTitle>
          </CardHeader>
          <CardContent>
            {servicesLoading ? (
              <div className="flex items-center justify-center py-8">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
              </div>
            ) : userServices && userServices.length > 0 ? (
              <div className="space-y-4">
                {userServices.map((service: any) => {
                  const IconComponent = getServiceIcon(service.type);
                  return (
                    <div key={service.id} className="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                      <div className="flex items-center space-x-4">
                        <div className="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                          <IconComponent className="w-6 h-6 text-primary" />
                        </div>
                        <div>
                          <h4 className="font-semibold text-gray-900">{service.name || 'Service'}</h4>
                          <p className="text-gray-600 text-sm">{service.domain || 'No domain assigned'}</p>
                        </div>
                      </div>
                      <div className="flex items-center space-x-4">
                        <Badge 
                          variant={service.status === 'active' ? 'default' : 'secondary'}
                          className={service.status === 'active' ? 'bg-green-500' : ''}
                        >
                          {service.status === 'active' ? (
                            <>
                              <CheckCircle className="w-3 h-3 mr-1" />
                              Active
                            </>
                          ) : (
                            <>
                              <Clock className="w-3 h-3 mr-1" />
                              {service.status}
                            </>
                          )}
                        </Badge>
                        <div className="text-right">
                          <p className="text-sm text-gray-600">€{service.price || '0.00'}/month</p>
                        </div>
                        <Button variant="outline" size="sm">
                          <Settings className="w-4 h-4 mr-2" />
                          Manage
                        </Button>
                      </div>
                    </div>
                  );
                })}
              </div>
            ) : (
              <div className="text-center py-8">
                <Server className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                <h3 className="text-lg font-medium text-gray-900 mb-2">No services yet</h3>
                <p className="text-gray-600 mb-4">Get started by ordering your first hosting service</p>
                <Link href="/order">
                  <Button>
                    <Plus className="w-4 h-4 mr-2" />
                    Order Your First Service
                  </Button>
                </Link>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Recent Orders */}
        <Card className="mt-8">
          <CardHeader>
            <CardTitle>Recent Orders</CardTitle>
          </CardHeader>
          <CardContent>
            {ordersLoading ? (
              <div className="flex items-center justify-center py-8">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
              </div>
            ) : orders && orders.length > 0 ? (
              <div className="space-y-4">
                {orders.slice(0, 5).map((order: any) => (
                  <div key={order.id} className="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                    <div>
                      <h4 className="font-semibold text-gray-900">Order #{order.id}</h4>
                      <p className="text-gray-600 text-sm">
                        {new Date(order.createdAt).toLocaleDateString()}
                      </p>
                    </div>
                    <div className="flex items-center space-x-4">
                      <span className="text-sm font-medium">€{order.amount}</span>
                      <Badge 
                        variant={order.status === 'paid' ? 'default' : 'secondary'}
                        className={order.status === 'paid' ? 'bg-green-500' : ''}
                      >
                        {order.status}
                      </Badge>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-center text-gray-600 py-8">No orders found</p>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
