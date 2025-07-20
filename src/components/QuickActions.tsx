import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { QrCode, MessageSquare, Bot, Settings, Send, RefreshCw } from 'lucide-react';
import { useToast } from '@/hooks/use-toast';
import { supabase } from '@/integrations/supabase/client';
import { useAuth } from '@/hooks/useAuth';

const QuickActions = () => {
  const { user } = useAuth();
  const { toast } = useToast();
  const [testMessage, setTestMessage] = useState('');
  const [testResponse, setTestResponse] = useState('');
  const [testing, setTesting] = useState(false);
  const [testDialogOpen, setTestDialogOpen] = useState(false);

  const handleTestAI = async () => {
    if (!testMessage.trim()) {
      toast({
        title: "Error",
        description: "Please enter a message to test",
        variant: "destructive",
      });
      return;
    }

    try {
      setTesting(true);
      setTestResponse('');

      // Get AI configuration
      const { data: config } = await supabase
        .from('ai_configurations')
        .select('*')
        .eq('user_id', user?.id)
        .single();

      if (!config || !config.ai_enabled) {
        toast({
          title: "AI Not Configured",
          description: "Please enable and configure AI settings first",
          variant: "destructive",
        });
        return;
      }

      if (!config.groq_api_key) {
        toast({
          title: "API Key Missing",
          description: "Please add your Groq API key in AI settings",
          variant: "destructive",
        });
        return;
      }

      // Test AI response using backend API
      const response = await fetch('http://localhost:3001/api/test-groq', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          message: testMessage,
          apiKey: config.groq_api_key,
          model: config.ai_model,
          systemPrompt: config.system_prompt
        })
      });

      const result = await response.json();

      if (result.success) {
        setTestResponse(result.response);
        toast({
          title: "Test Successful",
          description: "AI response generated successfully",
        });
      } else {
        throw new Error(result.error || 'Failed to generate response');
      }

    } catch (error) {
      console.error('Error testing AI:', error);
      toast({
        title: "Test Failed",
        description: error instanceof Error ? error.message : "Failed to generate AI response",
        variant: "destructive",
      });
    } finally {
      setTesting(false);
    }
  };

  const quickActionsData = [
    {
      icon: QrCode,
      label: "Scan QR Code",
      action: () => {
        toast({
          title: "QR Code Scanner",
          description: "This will open the WhatsApp QR code scanner",
        });
      }
    },
    {
      icon: Bot,
      label: "Test AI Reply",
      action: () => setTestDialogOpen(true)
    },
    {
      icon: MessageSquare,
      label: "View Messages",
      action: () => {
        toast({
          title: "Messages",
          description: "This will show your WhatsApp message history",
        });
      }
    },
    {
      icon: Settings,
      label: "Settings",
      action: () => {
        toast({
          title: "Settings",
          description: "This will open the application settings",
        });
      }
    }
  ];

  return (
    <div className="space-y-4">
      <h2 className="text-xl font-semibold">Quick Actions</h2>
      
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {quickActionsData.map((action, index) => (
          <Button
            key={index}
            variant="outline"
            className="h-20 flex flex-col space-y-2 hover:bg-muted/50"
            onClick={action.action}
          >
            <action.icon className="h-6 w-6" />
            <span className="text-sm">{action.label}</span>
          </Button>
        ))}
      </div>

      {/* Test AI Dialog */}
      <Dialog open={testDialogOpen} onOpenChange={setTestDialogOpen}>
        <DialogContent className="sm:max-w-[500px]">
          <DialogHeader>
            <DialogTitle className="flex items-center space-x-2">
              <Bot className="h-5 w-5" />
              <span>Test AI Reply</span>
            </DialogTitle>
            <DialogDescription>
              Send a test message to see how your AI would respond
            </DialogDescription>
          </DialogHeader>
          
          <div className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="test-message">Test Message</Label>
              <Textarea
                id="test-message"
                placeholder="Enter a message to test AI response..."
                value={testMessage}
                onChange={(e) => setTestMessage(e.target.value)}
                className="min-h-[80px]"
              />
            </div>

            <Button 
              onClick={handleTestAI} 
              disabled={testing || !testMessage.trim()}
              className="w-full"
            >
              {testing ? (
                <RefreshCw className="h-4 w-4 mr-2 animate-spin" />
              ) : (
                <Send className="h-4 w-4 mr-2" />
              )}
              {testing ? 'Generating Response...' : 'Test AI Response'}
            </Button>

            {testResponse && (
              <div className="space-y-2">
                <Label>AI Response</Label>
                <div className="p-3 bg-muted rounded-lg">
                  <p className="text-sm">{testResponse}</p>
                </div>
              </div>
            )}

            {testing && (
              <div className="flex items-center space-x-2 text-sm text-muted-foreground">
                <RefreshCw className="h-4 w-4 animate-spin" />
                <span>AI is thinking...</span>
              </div>
            )}
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default QuickActions;