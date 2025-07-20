import { useState, useEffect } from 'react';
import { useAuth } from '@/hooks/useAuth';
import { supabase } from '@/integrations/supabase/client';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Bot, Settings, Save, RefreshCw } from 'lucide-react';
import { useToast } from '@/hooks/use-toast';

interface AIConfig {
  id: string;
  ai_enabled: boolean;
  ai_model: string;
  system_prompt: string;
  keywords_trigger?: string[];
  auto_reply_delay: number;
  groq_api_key?: string;
}

const AIConfiguration = () => {
  const { user } = useAuth();
  const { toast } = useToast();
  const [config, setConfig] = useState<AIConfig | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [formData, setFormData] = useState({
    ai_enabled: false,
    ai_model: 'mixtral-8x7b-32768',
    system_prompt: 'Anda adalah asisten WhatsApp yang membantu menjawab pesan dengan ramah dan informatif.',
    keywords_trigger: '',
    auto_reply_delay: 3000,
    groq_api_key: ''
  });

  useEffect(() => {
    if (user) {
      fetchConfig();
    }
  }, [user]);

  const fetchConfig = async () => {
    try {
      const { data, error } = await supabase
        .from('ai_configurations')
        .select('*')
        .eq('user_id', user?.id)
        .maybeSingle();

      if (error && error.code !== 'PGRST116') {
        throw error;
      }

      if (data) {
        setConfig(data);
        setFormData({
          ai_enabled: data.ai_enabled,
          ai_model: data.ai_model,
          system_prompt: data.system_prompt,
          keywords_trigger: data.keywords_trigger?.join(', ') || '',
          auto_reply_delay: data.auto_reply_delay,
          groq_api_key: data.groq_api_key || ''
        });
      }
    } catch (error) {
      console.error('Error fetching AI config:', error);
      toast({
        title: "Error",
        description: "Failed to fetch AI configuration",
        variant: "destructive",
      });
    } finally {
      setLoading(false);
    }
  };

  const saveConfig = async () => {
    try {
      setSaving(true);
      
      const keywords = formData.keywords_trigger
        .split(',')
        .map(k => k.trim())
        .filter(k => k.length > 0);

      const configData = {
        user_id: user?.id,
        ai_enabled: formData.ai_enabled,
        ai_model: formData.ai_model,
        system_prompt: formData.system_prompt,
        keywords_trigger: keywords.length > 0 ? keywords : null,
        auto_reply_delay: formData.auto_reply_delay,
        groq_api_key: formData.groq_api_key || null
      };

      if (config) {
        // Update existing config
        const { data, error } = await supabase
          .from('ai_configurations')
          .update(configData)
          .eq('id', config.id)
          .select()
          .single();

        if (error) throw error;
        setConfig(data);
      } else {
        // Create new config
        const { data, error } = await supabase
          .from('ai_configurations')
          .insert(configData)
          .select()
          .single();

        if (error) throw error;
        setConfig(data);
      }

      toast({
        title: "Configuration Saved",
        description: "AI configuration has been updated successfully",
      });
    } catch (error) {
      console.error('Error saving AI config:', error);
      toast({
        title: "Error",
        description: "Failed to save AI configuration",
        variant: "destructive",
      });
    } finally {
      setSaving(false);
    }
  };

  const aiModels = [
    { value: 'mixtral-8x7b-32768', label: 'Mixtral 8x7B' },
    { value: 'llama2-70b-4096', label: 'Llama2 70B' },
    { value: 'gemma-7b-it', label: 'Gemma 7B' }
  ];

  if (loading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center space-x-2">
            <Bot className="h-5 w-5" />
            <span>AI Settings</span>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="animate-pulse space-y-4">
            <div className="h-4 bg-muted rounded w-3/4"></div>
            <div className="h-10 bg-muted rounded"></div>
            <div className="h-20 bg-muted rounded"></div>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center space-x-2">
          <Bot className="h-5 w-5" />
          <span>AI Settings</span>
        </CardTitle>
        <CardDescription>
          Configure your AI auto-reply behavior
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div className="space-y-6">
          {/* AI Status */}
          <div className="flex items-center justify-between">
            <div className="space-y-0.5">
              <Label>Enable AI Auto-Reply</Label>
              <p className="text-sm text-muted-foreground">
                Turn on automatic AI responses to WhatsApp messages
              </p>
            </div>
            <Switch
              checked={formData.ai_enabled}
              onCheckedChange={(checked) => 
                setFormData(prev => ({ ...prev, ai_enabled: checked }))
              }
            />
          </div>

          {formData.ai_enabled && (
            <>
              {/* Groq API Key */}
              <div className="space-y-2">
                <Label>Groq API Key</Label>
                <Input
                  type="password"
                  placeholder="Enter your Groq API key..."
                  value={formData.groq_api_key}
                  onChange={(e) => 
                    setFormData(prev => ({ ...prev, groq_api_key: e.target.value }))
                  }
                />
                <p className="text-sm text-muted-foreground">
                  Get your API key from <a href="https://console.groq.com/keys" target="_blank" rel="noopener noreferrer" className="text-blue-500 hover:underline">Groq Console</a>
                </p>
              </div>

              {/* AI Model Selection */}
              <div className="space-y-2">
                <Label>AI Model</Label>
                <Select
                  value={formData.ai_model}
                  onValueChange={(value) => 
                    setFormData(prev => ({ ...prev, ai_model: value }))
                  }
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Select AI model" />
                  </SelectTrigger>
                  <SelectContent>
                    {aiModels.map((model) => (
                      <SelectItem key={model.value} value={model.value}>
                        {model.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              {/* System Prompt */}
              <div className="space-y-2">
                <Label>System Prompt</Label>
                <Textarea
                  placeholder="Enter system prompt for AI..."
                  value={formData.system_prompt}
                  onChange={(e) => 
                    setFormData(prev => ({ ...prev, system_prompt: e.target.value }))
                  }
                  className="min-h-[100px]"
                />
                <p className="text-sm text-muted-foreground">
                  This prompt will guide how the AI responds to messages
                </p>
              </div>

              {/* Keywords Trigger */}
              <div className="space-y-2">
                <Label>Trigger Keywords (Optional)</Label>
                <Input
                  placeholder="keyword1, keyword2, keyword3"
                  value={formData.keywords_trigger}
                  onChange={(e) => 
                    setFormData(prev => ({ ...prev, keywords_trigger: e.target.value }))
                  }
                />
                <p className="text-sm text-muted-foreground">
                  AI will only respond to messages containing these keywords. Leave empty to respond to all messages.
                </p>
              </div>

              {/* Auto Reply Delay */}
              <div className="space-y-2">
                <Label>Auto Reply Delay (milliseconds)</Label>
                <Input
                  type="number"
                  min="1000"
                  max="30000"
                  step="1000"
                  value={formData.auto_reply_delay}
                  onChange={(e) => 
                    setFormData(prev => ({ ...prev, auto_reply_delay: parseInt(e.target.value) || 3000 }))
                  }
                />
                <p className="text-sm text-muted-foreground">
                  Delay before sending auto-reply (minimum 1 second)
                </p>
              </div>
            </>
          )}

          {/* Status Badge */}
          <div>
            <Badge variant={formData.ai_enabled ? "default" : "outline"}>
              {formData.ai_enabled ? "AI Enabled" : "AI Disabled"}
            </Badge>
          </div>

          {/* Save Button */}
          <Button onClick={saveConfig} disabled={saving} className="w-full">
            {saving ? (
              <RefreshCw className="h-4 w-4 mr-2 animate-spin" />
            ) : (
              <Save className="h-4 w-4 mr-2" />
            )}
            Save Configuration
          </Button>
        </div>
      </CardContent>
    </Card>
  );
};

export default AIConfiguration;