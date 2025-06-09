import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useToast } from "@/hooks/use-toast";
import { useLocation } from "wouter";
import { apiRequest } from "@/lib/queryClient";
import { registerSchema } from "@shared/schema";
import { z } from "zod";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form";
import { Eye, EyeOff, UserPlus, ArrowLeft } from "lucide-react";
import { Link } from "wouter";

export default function Register() {
  const [, setLocation] = useLocation();
  const [showPassword, setShowPassword] = useState(false);
  const { toast } = useToast();
  const queryClient = useQueryClient();

  const form = useForm<z.infer<typeof registerSchema>>({
    resolver: zodResolver(registerSchema),
    defaultValues: {
      email: "",
      password: "",
      firstName: "",
      lastName: "",
    },
  });

  const registerMutation = useMutation({
    mutationFn: async (data) => {
      const response = await apiRequest("POST", "/api/auth/register", data);
      return response.json();
    },
    onSuccess: (data) => {
      toast({
        title: "Registrierung erfolgreich",
        description: `Willkommen bei SpectraHost, ${data.user.firstName}!`,
      });
      queryClient.invalidateQueries({ queryKey: ["/api/auth/user"] });
      setLocation("/dashboard");
    },
    onError: (error) => {
      toast({
        title: "Registrierung fehlgeschlagen",
        description: error.message || "Bitte überprüfen Sie Ihre Eingaben",
        variant: "destructive",
      });
    },
  });

  const onSubmit = (data) => {
    registerMutation.mutate(data);
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-gradient-to-r from-primary/20 to-accent/20"></div>
      
      <Card className="w-full max-w-md relative z-10 glassmorphism border-white/20">
        <CardHeader className="text-center">
          <div className="flex items-center justify-center mb-4">
            <Link href="/">
              <Button variant="ghost" size="sm" className="text-white/70 hover:text-white">
                <ArrowLeft className="w-4 h-4 mr-2" />
                Zurück zur Startseite
              </Button>
            </Link>
          </div>
          <CardTitle className="text-2xl font-bold text-white mb-2">Registrieren</CardTitle>
          <p className="text-white/70">Erstellen Sie Ihr SpectraHost-Konto</p>
        </CardHeader>
        <CardContent>
          <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
              <div className="grid grid-cols-2 gap-4">
                <FormField
                  control={form.control}
                  name="firstName"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className="text-white">Vorname</FormLabel>
                      <FormControl>
                        <Input
                          placeholder="Max"
                          className="bg-white/10 border-white/20 text-white placeholder:text-white/50"
                          {...field}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="lastName"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className="text-white">Nachname</FormLabel>
                      <FormControl>
                        <Input
                          placeholder="Mustermann"
                          className="bg-white/10 border-white/20 text-white placeholder:text-white/50"
                          {...field}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>

              <FormField
                control={form.control}
                name="email"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className="text-white">E-Mail-Adresse</FormLabel>
                    <FormControl>
                      <Input
                        type="email"
                        placeholder="max@mustermann.de"
                        className="bg-white/10 border-white/20 text-white placeholder:text-white/50"
                        {...field}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="password"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className="text-white">Passwort</FormLabel>
                    <FormControl>
                      <div className="relative">
                        <Input
                          type={showPassword ? "text" : "password"}
                          placeholder="Mindestens 6 Zeichen"
                          className="bg-white/10 border-white/20 text-white placeholder:text-white/50 pr-10"
                          {...field}
                        />
                        <Button
                          type="button"
                          variant="ghost"
                          size="sm"
                          className="absolute right-0 top-0 h-full px-3 text-white/50 hover:text-white"
                          onClick={() => setShowPassword(!showPassword)}
                        >
                          {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                        </Button>
                      </div>
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <Button
                type="submit"
                className="w-full bg-primary hover:bg-primary/90 text-white"
                disabled={registerMutation.isPending}
              >
                {registerMutation.isPending ? (
                  "Registrierung läuft..."
                ) : (
                  <>
                    <UserPlus className="w-4 h-4 mr-2" />
                    Konto erstellen
                  </>
                )}
              </Button>
            </form>
          </Form>

          <div className="mt-6 text-center">
            <p className="text-white/70">
              Bereits ein Konto?{" "}
              <Link href="/login" className="text-primary hover:text-primary/80 font-medium">
                Jetzt anmelden
              </Link>
            </p>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}