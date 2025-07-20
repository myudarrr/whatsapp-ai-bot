import { useState, useEffect } from 'react';
import { useAuth } from '@/hooks/useAuth';
import { supabase } from '@/integrations/supabase/client';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { QrCode, RefreshCw, Phone, CheckCircle, XCircle } from 'lucide-react';
import { useToast } from '@/hooks/use-toast';
import { io, Socket } from 'socket.io-client';

interface WhatsAppConnection {
  id: string;
  status: 'connected' | 'disconnected' | 'connecting' | 'error';
  qr_code?: string;
  phone_number?: string;
  last_connected_at?: string;
}

const WhatsAppConnection = () => {
  const { user } = useAuth();
  const { toast } = useToast();
  const [connection, setConnection] = useState<WhatsAppConnection | null>(null);
  const [loading, setLoading] = useState(true);
  const [qrCode, setQrCode] = useState<string | null>(null);
  const [socket, setSocket] = useState<Socket | null>(null);
  const [isConnecting, setIsConnecting] = useState(false);

  useEffect(() => {
    if (user) {
      fetchConnection();
      initializeSocket();
    }

    return () => {
      if (socket) {
        socket.disconnect();
      }
    };
  }, [user]);

  const initializeSocket = () => {
    const newSocket = io('http://localhost:3001');
    setSocket(newSocket);

    // Listen for QR code
    newSocket.on('qr-code', (data) => {
      console.log('QR Code received:', data);
      setQrCode(data.qr);
      updateConnectionStatus('connecting');
      
      toast({
        title: "QR Code Generated",
        description: "Scan the QR code with your WhatsApp mobile app",
      });
    });

    // Listen for client ready
    newSocket.on('client-ready', (data) => {
      console.log('Client ready:', data);
      setQrCode(null);
      updateConnectionStatus('connected', data.phoneNumber);
      
      toast({
        title: "Connected!",
        description: `WhatsApp connected successfully as ${data.phoneNumber}`,
      });
    });

    // Listen for authentication
    newSocket.on('authenticated', (data) => {
      console.log('Authenticated:', data);
    });

    // Listen for auth failure
    newSocket.on('auth-failure', (data) => {
      console.error('Auth failure:', data);
      setQrCode(null);
      updateConnectionStatus('error');
      
      toast({
        title: "Authentication Failed",
        description: "Failed to authenticate with WhatsApp",
        variant: "destructive",
      });
    });

    // Listen for disconnection
    newSocket.on('disconnected', (data) => {
      console.log('Disconnected:', data);
      setQrCode(null);
      updateConnectionStatus('disconnected');
      
      toast({
        title: "Disconnected",
        description: "WhatsApp has been disconnected",
        variant: "destructive",
      });
    });

    // Listen for already initialized
    newSocket.on('already-initialized', (data) => {
      console.log('Already initialized:', data);
      toast({
        title: "Already Connected",
        description: "WhatsApp is already connected",
      });
    });

    return newSocket;
  };

  const fetchConnection = async () => {
    try {
      const { data, error } = await supabase
        .from('whatsapp_connections')
        .select('*')
        .eq('user_id', user?.id)
        .maybeSingle();

      if (error && error.code !== 'PGRST116') {
        throw error;
      }

      setConnection(data);
    } catch (error) {
      console.error('Error fetching connection:', error);
      toast({
        title: "Error",
        description: "Failed to fetch WhatsApp connection status",
        variant: "destructive",
      });
    } finally {
      setLoading(false);
    }
  };

  const updateConnectionStatus = async (status: string, phoneNumber?: string) => {
    try {
      const updateData: any = {
        status,
        updated_at: new Date().toISOString()
      };

      if (status === 'connected' && phoneNumber) {
        updateData.phone_number = phoneNumber;
        updateData.last_connected_at = new Date().toISOString();
        updateData.qr_code = null;
      } else if (status === 'disconnected') {
        updateData.phone_number = null;
        updateData.last_connected_at = null;
        updateData.qr_code = null;
      }

      if (!connection) {
        // Create new connection
        const { data, error } = await supabase
          .from('whatsapp_connections')
          .insert({
            user_id: user?.id,
            ...updateData
          })
          .select()
          .single();

        if (error) throw error;
        setConnection(data);
      } else {
        // Update existing connection
        const { data, error } = await supabase
          .from('whatsapp_connections')
          .update(updateData)
          .eq('id', connection.id)
          .select()
          .single();

        if (error) throw error;
        setConnection(data);
      }
    } catch (error) {
      console.error('Error updating connection status:', error);
    }
  };

  const connectWhatsApp = async () => {
    if (!socket || !user) return;

    try {
      setIsConnecting(true);
      setLoading(true);
      
      // Initialize WhatsApp connection
      socket.emit('initialize-whatsapp', { userId: user.id });
      
    } catch (error) {
      console.error('Error connecting WhatsApp:', error);
      toast({
        title: "Error",
        description: "Failed to initialize WhatsApp connection",
        variant: "destructive",
      });
    } finally {
      setIsConnecting(false);
      setLoading(false);
    }
  };

  const disconnect = async () => {
    if (!socket || !user) return;

    try {
      setLoading(true);
      
      // Disconnect WhatsApp
      socket.emit('disconnect-whatsapp', { userId: user.id });
      
      setQrCode(null);
      
    } catch (error) {
      console.error('Error disconnecting:', error);
      toast({
        title: "Error",
        description: "Failed to disconnect WhatsApp",
        variant: "destructive",
      });
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = () => {
    if (!connection) {
      return <Badge variant="outline">Not Setup</Badge>;
    }

    switch (connection.status) {
      case 'connected':
        return (
          <Badge className="bg-green-100 text-green-800 border-green-200">
            <CheckCircle className="h-3 w-3 mr-1" />
            Connected
          </Badge>
        );
      case 'connecting':
        return (
          <Badge className="bg-yellow-100 text-yellow-800 border-yellow-200">
            <RefreshCw className="h-3 w-3 mr-1 animate-spin" />
            Connecting
          </Badge>
        );
      default:
        return (
          <Badge variant="outline">
            <XCircle className="h-3 w-3 mr-1" />
            Disconnected
          </Badge>
        );
    }
  };

  if (loading && !connection) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center space-x-2">
            <QrCode className="h-5 w-5" />
            <span>WhatsApp Connection</span>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="animate-pulse">
            <div className="h-4 bg-muted rounded w-3/4 mb-4"></div>
            <div className="h-10 bg-muted rounded"></div>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center space-x-2">
          <QrCode className="h-5 w-5" />
          <span>WhatsApp Connection</span>
        </CardTitle>
        <CardDescription>
          Connect your WhatsApp account to start auto-replies
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          {getStatusBadge()}
          
          {connection?.phone_number && (
            <div className="flex items-center space-x-2 text-sm text-muted-foreground">
              <Phone className="h-4 w-4" />
              <span>{connection.phone_number}</span>
            </div>
          )}

          {connection?.last_connected_at && (
            <div className="text-sm text-muted-foreground">
              Last connected: {new Date(connection.last_connected_at).toLocaleString()}
            </div>
          )}

          {qrCode && (
            <div className="space-y-2">
              <p className="text-sm text-muted-foreground">
                Scan this QR code with your WhatsApp mobile app:
              </p>
              <div className="flex justify-center p-4 bg-white rounded-lg border">
                <img 
                  src={`https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(qrCode)}`}
                  alt="WhatsApp QR Code"
                  className="w-48 h-48"
                />
              </div>
            </div>
          )}

          <div className="flex space-x-2">
            {connection?.status === 'connected' ? (
              <Button variant="outline" onClick={disconnect} disabled={loading}>
                <XCircle className="h-4 w-4 mr-2" />
                Disconnect
              </Button>
            ) : (
              <Button onClick={connectWhatsApp} disabled={loading || isConnecting} className="w-full">
                <QrCode className="h-4 w-4 mr-2" />
                {isConnecting || connection?.status === 'connecting' ? 'Connecting...' : 'Connect WhatsApp'}
              </Button>
            )}
          </div>
        </div>
      </CardContent>
    </Card>
  );
};

export default WhatsAppConnection;