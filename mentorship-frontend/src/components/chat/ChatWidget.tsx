'use client';

import React, { useState, useEffect, useRef } from 'react';
import { MessageCircle, X, ChevronLeft, Send, User } from 'lucide-react';
import { api, authService } from '@/lib/api';

type Conversation = {
  id: number;
  other_user: {
    id: number;
    name: string;
    profile_image: string | null;
    role: string;
  };
  last_message: {
    body: string;
    created_at: string;
  } | null;
  unread_count: number;
};

type Message = {
  id: number;
  sender_id: number;
  body: string;
  created_at: string;
};

export function ChatWidget() {
  const [isOpen, setIsOpen] = useState(false);
  const [conversations, setConversations] = useState<Conversation[]>([]);
  const [activeChat, setActiveChat] = useState<Conversation | null>(null);
  const [messages, setMessages] = useState<Message[]>([]);
  const [inputText, setInputText] = useState('');
  const [loading, setLoading] = useState(false);
  
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const pollIntervalRef = useRef<NodeJS.Timeout | null>(null);

  const currentUser = authService.getUser();

  // Load conversations on mount
  useEffect(() => {
    if (currentUser?.id) {
      loadConversations();
      // Poll for new conversations/unread counts every 30s
      const convInterval = setInterval(loadConversations, 30000);
      return () => clearInterval(convInterval);
    }
  }, [currentUser?.id]);

  // Scroll to bottom when messages change
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  // Polling for active chat
  useEffect(() => {
    if (activeChat && isOpen) {
      pollIntervalRef.current = setInterval(() => {
        pollMessages(activeChat.id);
      }, 3000); // Poll every 3 seconds
    }

    return () => {
      if (pollIntervalRef.current) clearInterval(pollIntervalRef.current);
    };
  }, [activeChat, isOpen]);

  const loadConversations = async () => {
    try {
      const res = await api.get('/conversations');
      setConversations(res.conversations || []);
    } catch (err) {
      console.error('Failed to load conversations', err);
    }
  };

  const openChat = async (conv: Conversation) => {
    setActiveChat(conv);
    setLoading(true);
    try {
      const res = await api.get(`/messages/${conv.other_user.id}`);
      setMessages(res.messages || []);
      
      // Update unread count locally
      setConversations(prev => 
        prev.map(c => c.id === conv.id ? { ...c, unread_count: 0 } : c)
      );
    } catch (err) {
      console.error('Failed to load messages', err);
    } finally {
      setLoading(false);
    }
  };

  const pollMessages = async (conversationId: number) => {
    try {
      // Find the last message ID
      let afterId = 0;
      setMessages(currentMessages => {
        if (currentMessages.length > 0) {
          afterId = currentMessages[currentMessages.length - 1].id;
        }
        return currentMessages;
      });

      const res = await api.get(`/messages/poll/${conversationId}?after_id=${afterId}`);
      if (res.messages && res.messages.length > 0) {
        setMessages(prev => [...prev, ...res.messages]);
      }
    } catch (err) {
      console.error('Polling failed', err);
    }
  };

  const sendMessage = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!inputText.trim() || !activeChat) return;

    const tempText = inputText;
    setInputText('');

    // Optimistic UI update
    const tempMsg: Message = {
      id: Date.now(),
      sender_id: currentUser.id,
      body: tempText,
      created_at: new Date().toISOString(),
    };
    setMessages(prev => [...prev, tempMsg]);

    try {
      const res = await api.post('/messages/send', {
        receiver_id: activeChat.other_user.id,
        body: tempText
      });
      // Replace temp message with real message (optional, but good for ID syncing)
      setMessages(prev => prev.map(m => m.id === tempMsg.id ? res.message : m));
      loadConversations(); // Update latest message preview
    } catch (err) {
      console.error('Failed to send message', err);
      // Revert optimistic update on fail
      setMessages(prev => prev.filter(m => m.id !== tempMsg.id));
      setInputText(tempText);
    }
  };

  const totalUnread = conversations.reduce((sum, c) => sum + c.unread_count, 0);

  if (!currentUser) return null;

  return (
    <div className="fixed bottom-6 right-6 z-50 flex flex-col items-end">
      {/* Chat Popover */}
      {isOpen && (
        <div className="bg-white w-80 sm:w-96 h-[500px] mb-4 rounded-2xl shadow-2xl border border-purple-100 flex flex-col overflow-hidden animate-fade-in-up">
          {/* Header */}
          <div className="bg-gradient-to-r from-purple-600 to-fuchsia-600 p-4 text-white flex items-center justify-between shrink-0">
            {activeChat ? (
              <div className="flex items-center gap-3">
                <button 
                  onClick={() => setActiveChat(null)}
                  className="p-1 hover:bg-white/20 rounded-full transition"
                >
                  <ChevronLeft className="w-5 h-5" />
                </button>
                <div className="flex items-center gap-2">
                  <div className="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center overflow-hidden">
                    {activeChat.other_user.profile_image ? (
                      <img src={activeChat.other_user.profile_image} alt="" className="w-full h-full object-cover" />
                    ) : (
                      <User className="w-4 h-4 text-white" />
                    )}
                  </div>
                  <span className="font-semibold">{activeChat.other_user.name}</span>
                </div>
              </div>
            ) : (
              <div className="flex items-center gap-2">
                <MessageCircle className="w-5 h-5" />
                <span className="font-semibold">Messages</span>
              </div>
            )}
            <button 
              onClick={() => setIsOpen(false)}
              className="p-1 hover:bg-white/20 rounded-full transition"
            >
              <X className="w-5 h-5" />
            </button>
          </div>

          {/* Body */}
          <div className="flex-1 bg-gray-50 flex flex-col overflow-hidden">
            {!activeChat ? (
              // Conversations List
              <div className="flex-1 overflow-y-auto p-2">
                {conversations.length === 0 ? (
                  <div className="h-full flex flex-col items-center justify-center text-gray-400 p-6 text-center">
                    <MessageCircle className="w-12 h-12 mb-2 opacity-50" />
                    <p className="text-sm">No messages yet. When you match with a mentor, you can chat here!</p>
                  </div>
                ) : (
                  <div className="space-y-1">
                    {conversations.map(conv => (
                      <button
                        key={conv.id}
                        onClick={() => openChat(conv)}
                        className="w-full p-3 flex items-center gap-3 hover:bg-white rounded-xl transition text-left"
                      >
                        <div className="relative shrink-0">
                          <div className="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center overflow-hidden border border-purple-200">
                            {conv.other_user.profile_image ? (
                              <img src={conv.other_user.profile_image} alt="" className="w-full h-full object-cover" />
                            ) : (
                              <User className="w-6 h-6 text-purple-400" />
                            )}
                          </div>
                          {conv.unread_count > 0 && (
                            <div className="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full flex items-center justify-center text-[10px] text-white font-bold border-2 border-white">
                              {conv.unread_count}
                            </div>
                          )}
                        </div>
                        <div className="flex-1 min-w-0">
                          <div className="flex justify-between items-baseline mb-0.5">
                            <p className="font-semibold text-gray-900 truncate">{conv.other_user.name}</p>
                            {conv.last_message && (
                              <span className="text-[10px] text-gray-400">
                                {new Date(conv.last_message.created_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}
                              </span>
                            )}
                          </div>
                          <p className="text-xs text-gray-500 truncate">
                            {conv.last_message ? conv.last_message.body : 'Start chatting...'}
                          </p>
                        </div>
                      </button>
                    ))}
                  </div>
                )}
              </div>
            ) : (
              // Active Chat
              <>
                <div className="flex-1 overflow-y-auto p-4 space-y-4">
                  {loading ? (
                    <div className="h-full flex items-center justify-center">
                      <div className="animate-spin w-6 h-6 border-2 border-purple-500 border-t-transparent rounded-full"></div>
                    </div>
                  ) : messages.length === 0 ? (
                    <div className="h-full flex flex-col items-center justify-center text-gray-400">
                      <p className="text-sm">Say hello! 👋</p>
                    </div>
                  ) : (
                    messages.map((msg, i) => {
                      const isMe = msg.sender_id === currentUser.id;
                      return (
                        <div key={msg.id || i} className={`flex ${isMe ? 'justify-end' : 'justify-start'}`}>
                          <div 
                            className={`max-w-[80%] px-4 py-2 rounded-2xl text-sm ${
                              isMe 
                                ? 'bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white rounded-tr-sm' 
                                : 'bg-white border border-gray-100 text-gray-800 rounded-tl-sm shadow-sm'
                            }`}
                          >
                            {msg.body}
                          </div>
                        </div>
                      );
                    })
                  )}
                  <div ref={messagesEndRef} />
                </div>
                
                {/* Input Area */}
                <form onSubmit={sendMessage} className="p-3 bg-white border-t border-gray-100 flex gap-2 shrink-0">
                  <input
                    type="text"
                    value={inputText}
                    onChange={(e) => setInputText(e.target.value)}
                    placeholder="Type a message..."
                    className="flex-1 bg-gray-50 border border-gray-200 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500"
                  />
                  <button 
                    type="submit"
                    disabled={!inputText.trim()}
                    className="w-10 h-10 bg-purple-600 text-white rounded-full flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed hover:bg-purple-700 transition"
                  >
                    <Send className="w-4 h-4 ml-0.5" />
                  </button>
                </form>
              </>
            )}
          </div>
        </div>
      )}

      {/* Floating Button */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="w-14 h-14 bg-gradient-to-r from-purple-600 to-fuchsia-600 rounded-full shadow-lg shadow-purple-500/30 flex items-center justify-center text-white hover:scale-105 hover:shadow-xl transition-all relative z-50"
      >
        {isOpen ? <X className="w-6 h-6" /> : <MessageCircle className="w-6 h-6" />}
        
        {/* Unread Badge on Button */}
        {!isOpen && totalUnread > 0 && (
          <div className="absolute -top-1 -right-1 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center text-xs text-white font-bold border-2 border-white shadow-sm">
            {totalUnread > 9 ? '9+' : totalUnread}
          </div>
        )}
      </button>
    </div>
  );
}
