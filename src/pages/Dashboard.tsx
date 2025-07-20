import { useAuth } from '@/hooks/useAuth';
import { Button } from '@/components/ui/button';
import { LogOut, MessageSquare } from 'lucide-react';
import WhatsAppConnection from '@/components/WhatsAppConnection';
import AIConfiguration from '@/components/AIConfiguration';
import MessageStatistics from '@/components/MessageStatistics';
import QuickActions from '@/components/QuickActions';

const Dashboard = () => {
  const { user, signOut } = useAuth();

  const handleSignOut = async () => {
    await signOut();
  };

  return (
    <div className="min-h-screen bg-background">
      {/* Header */}
      <header className="border-b">
        <div className="container mx-auto px-4 py-4 flex justify-between items-center">
          <div className="flex items-center space-x-2">
            <MessageSquare className="h-8 w-8 text-primary" />
            <h1 className="text-2xl font-bold">WhatsApp AI Assistant</h1>
          </div>
          <div className="flex items-center space-x-4">
            <span className="text-sm text-muted-foreground">
              Welcome, {user?.email}
            </span>
            <Button variant="outline" onClick={handleSignOut}>
              <LogOut className="h-4 w-4 mr-2" />
              Sign Out
            </Button>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="container mx-auto px-4 py-8">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          
          {/* WhatsApp Connection Status */}
          <WhatsAppConnection />

          {/* AI Configuration */}
          <AIConfiguration />

          {/* Message Statistics */}
          <MessageStatistics />

        </div>

        {/* Quick Actions */}
        <div className="mt-8">
          <QuickActions />
        </div>
      </main>
    </div>
  );
};

export default Dashboard;