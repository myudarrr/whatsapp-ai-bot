import { useState, useEffect } from 'react';
import { useAuth } from '@/hooks/useAuth';
import { supabase } from '@/integrations/supabase/client';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { MessageSquare, TrendingUp, Clock, CheckCircle, XCircle } from 'lucide-react';

interface MessageStats {
  totalMessages: number;
  aiReplies: number;
  successRate: number;
  avgResponseTime: number;
  todayMessages: number;
  todayReplies: number;
}

const MessageStatistics = () => {
  const { user } = useAuth();
  const [stats, setStats] = useState<MessageStats>({
    totalMessages: 0,
    aiReplies: 0,
    successRate: 0,
    avgResponseTime: 0,
    todayMessages: 0,
    todayReplies: 0
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (user) {
      fetchStatistics();
    }
  }, [user]);

  const fetchStatistics = async () => {
    try {
      // Get total messages
      const { count: totalMessages } = await supabase
        .from('chat_messages')
        .select('*', { count: 'exact', head: true })
        .eq('user_id', user?.id);

      // Get AI replies
      const { count: aiReplies } = await supabase
        .from('chat_messages')
        .select('*', { count: 'exact', head: true })
        .eq('user_id', user?.id)
        .eq('ai_replied', true);

      // Get today's messages
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      
      const { count: todayMessages } = await supabase
        .from('chat_messages')
        .select('*', { count: 'exact', head: true })
        .eq('user_id', user?.id)
        .gte('created_at', today.toISOString());

      const { count: todayReplies } = await supabase
        .from('chat_messages')
        .select('*', { count: 'exact', head: true })
        .eq('user_id', user?.id)
        .eq('ai_replied', true)
        .gte('created_at', today.toISOString());

      // Get average response time from auto_reply_logs
      const { data: responseTimes } = await supabase
        .from('auto_reply_logs')
        .select('response_time_ms')
        .eq('user_id', user?.id)
        .eq('success', true)
        .not('response_time_ms', 'is', null);

      let avgResponseTime = 0;
      if (responseTimes && responseTimes.length > 0) {
        const totalTime = responseTimes.reduce((sum, log) => sum + (log.response_time_ms || 0), 0);
        avgResponseTime = Math.round(totalTime / responseTimes.length);
      }

      // Calculate success rate
      const successRate = totalMessages && totalMessages > 0 
        ? Math.round((aiReplies || 0) / totalMessages * 100) 
        : 0;

      setStats({
        totalMessages: totalMessages || 0,
        aiReplies: aiReplies || 0,
        successRate,
        avgResponseTime,
        todayMessages: todayMessages || 0,
        todayReplies: todayReplies || 0
      });

    } catch (error) {
      console.error('Error fetching statistics:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center space-x-2">
            <MessageSquare className="h-5 w-5" />
            <span>Message Statistics</span>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="animate-pulse space-y-3">
            {Array.from({ length: 4 }).map((_, i) => (
              <div key={i} className="flex justify-between">
                <div className="h-4 bg-muted rounded w-1/2"></div>
                <div className="h-4 bg-muted rounded w-1/4"></div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center space-x-2">
          <MessageSquare className="h-5 w-5" />
          <span>Message Statistics</span>
        </CardTitle>
        <CardDescription>
          View your auto-reply statistics
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          {/* Today's Stats */}
          <div className="border-b pb-4">
            <h4 className="font-medium text-sm text-muted-foreground mb-2">Today</h4>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-1">
                <div className="flex items-center space-x-1">
                  <MessageSquare className="h-3 w-3 text-muted-foreground" />
                  <span className="text-sm">Messages</span>
                </div>
                <div className="text-2xl font-bold">{stats.todayMessages}</div>
              </div>
              <div className="space-y-1">
                <div className="flex items-center space-x-1">
                  <CheckCircle className="h-3 w-3 text-green-500" />
                  <span className="text-sm">AI Replies</span>
                </div>
                <div className="text-2xl font-bold text-green-600">{stats.todayReplies}</div>
              </div>
            </div>
          </div>

          {/* All Time Stats */}
          <div className="space-y-3">
            <h4 className="font-medium text-sm text-muted-foreground">All Time</h4>
            
            <div className="flex justify-between items-center">
              <div className="flex items-center space-x-2">
                <MessageSquare className="h-4 w-4 text-muted-foreground" />
                <span className="text-sm">Total Messages</span>
              </div>
              <span className="font-semibold">{stats.totalMessages.toLocaleString()}</span>
            </div>

            <div className="flex justify-between items-center">
              <div className="flex items-center space-x-2">
                <CheckCircle className="h-4 w-4 text-green-500" />
                <span className="text-sm">AI Replies Sent</span>
              </div>
              <span className="font-semibold text-green-600">{stats.aiReplies.toLocaleString()}</span>
            </div>

            <div className="flex justify-between items-center">
              <div className="flex items-center space-x-2">
                <TrendingUp className="h-4 w-4 text-blue-500" />
                <span className="text-sm">Success Rate</span>
              </div>
              <span className="font-semibold text-blue-600">
                {stats.successRate}%
              </span>
            </div>

            <div className="flex justify-between items-center">
              <div className="flex items-center space-x-2">
                <Clock className="h-4 w-4 text-orange-500" />
                <span className="text-sm">Avg Response Time</span>
              </div>
              <span className="font-semibold text-orange-600">
                {stats.avgResponseTime > 0 ? `${stats.avgResponseTime}ms` : '--'}
              </span>
            </div>
          </div>

          {stats.totalMessages === 0 && (
            <div className="text-center py-4 text-muted-foreground">
              <MessageSquare className="h-8 w-8 mx-auto mb-2 opacity-50" />
              <p className="text-sm">No messages yet</p>
              <p className="text-xs">Connect WhatsApp to start collecting statistics</p>
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  );
};

export default MessageStatistics;