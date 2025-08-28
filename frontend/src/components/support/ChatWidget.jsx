import React, { useState, useEffect, useRef } from 'react';
import { useAuth } from '../../contexts/AuthContext';
import { supportAPI } from '../../services/api';
import {
  FiX,
  FiSend,
  FiMinimize2,
  FiMaximize2,
  FiHelpCircle,
  FiMessageSquare,
  FiPaperclip,
  FiSmile,
  FiClock,
  FiBookOpen
} from 'react-icons/fi';

const ChatWidget = () => {
  const { user } = useAuth();
  const [isOpen, setIsOpen] = useState(false);
  const [isMinimized, setIsMinimized] = useState(false);
  const [messages, setMessages] = useState([]);
  const [inputMessage, setInputMessage] = useState('');
  const [isTyping, setIsTyping] = useState(false);
  const [sessionId, setSessionId] = useState(null);
  const [knowledgeBase, setKnowledgeBase] = useState([]);
  const [suggestedQuestions, setSuggestedQuestions] = useState([]);
  const messagesEndRef = useRef(null);

  // Pre-defined welcome messages and suggested questions
  const welcomeMessages = [
    {
      id: 'welcome-1',
      type: 'bot',
      content: `👋 Hi there! I'm your Ardent POS assistant. How can I help you today?`,
      timestamp: new Date()
    }
  ];

  const defaultSuggestions = [
    "How do I set up my POS system?",
    "How to process a sale?",
    "How to manage inventory?",
    "How to create a support ticket?",
    "How to view sales reports?",
    "How to add new products?",
    "How to manage customers?",
    "How to configure payment methods?"
  ];

  useEffect(() => {
    // Initialize chat session
    initializeChat();
    loadKnowledgeBase();
  }, []);

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  const initializeChat = async () => {
    try {
      // For public users, just start with welcome messages
      // Chat sessions are only needed for authenticated users
      if (user) {
        // Create a new chat session for authenticated users
        const sessionResponse = await supportAPI.createChatSession();
        if (sessionResponse?.data?.session_id) {
          setSessionId(sessionResponse.data.session_id);
          
          // Load previous messages if any
          if (sessionResponse.data.messages && sessionResponse.data.messages.length > 0) {
            setMessages(sessionResponse.data.messages);
          } else {
            setMessages(welcomeMessages);
          }
        } else {
          setMessages(welcomeMessages);
        }
      } else {
        // Public users start with welcome messages
        setMessages(welcomeMessages);
      }
    } catch (error) {
      console.error('Error initializing chat:', error);
      setMessages(welcomeMessages);
    }
  };

  const loadKnowledgeBase = async () => {
    try {
      const response = await supportAPI.getKnowledgebase();
      const articles = response.data?.articles || [];
      setKnowledgeBase(articles);
      
      // Generate suggested questions based on available articles
      const suggestions = articles
        .slice(0, 8)
        .map(article => article.title?.replace(/^#\s*/, '').replace(/\?$/, '?') || 'How can I help you?')
        .filter(title => title && title.length < 50);
      
      setSuggestedQuestions(suggestions.length > 0 ? suggestions : defaultSuggestions);
    } catch (error) {
      console.error('Error loading knowledge base:', error);
      setSuggestedQuestions(defaultSuggestions);
    }
  };

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  const handleSendMessage = async (message = inputMessage) => {
    if (!message.trim()) return;

    const userMessage = {
      id: Date.now(),
      type: 'user',
      content: message,
      timestamp: new Date()
    };

    setMessages(prev => [...prev, userMessage]);
    setInputMessage('');
    setIsTyping(true);

    try {
      // First, try to find a relevant knowledge base article
      const relevantArticle = findRelevantArticle(message);
      
      let botResponse;
      
      if (relevantArticle) {
        // Provide answer based on knowledge base
        botResponse = {
          id: Date.now() + 1,
          type: 'bot',
          content: generateArticleResponse(relevantArticle, message),
          timestamp: new Date(),
          article: relevantArticle
        };
      } else {
        // Send to chat API for general response
        const response = await supportAPI.sendChatMessage(sessionId, message);
        botResponse = {
          id: Date.now() + 1,
          type: 'bot',
          content: response.data?.message || response.message || "I'm here to help! Could you please provide more details about your question?",
          timestamp: new Date()
        };
      }

      setMessages(prev => [...prev, botResponse]);
    } catch (error) {
      console.error('Error sending message:', error);
      const errorResponse = {
        id: Date.now() + 1,
        type: 'bot',
        content: "I'm having trouble connecting right now. Please try again or create a support ticket for immediate assistance.",
        timestamp: new Date()
      };
      setMessages(prev => [...prev, errorResponse]);
    } finally {
      setIsTyping(false);
    }
  };

  const findRelevantArticle = (query) => {
    const searchTerms = query.toLowerCase().split(' ');
    
    // Find the most relevant article based on title and content
    let bestMatch = null;
    let bestScore = 0;

    knowledgeBase.forEach(article => {
      const title = article.title.toLowerCase();
      const content = article.content.toLowerCase();
      const tags = article.tags.toLowerCase();
      
      let score = 0;
      
      searchTerms.forEach(term => {
        if (title.includes(term)) score += 3;
        if (content.includes(term)) score += 1;
        if (tags.includes(term)) score += 2;
      });
      
      if (score > bestScore) {
        bestScore = score;
        bestMatch = article;
      }
    });

    return bestScore > 2 ? bestMatch : null;
  };

  const generateArticleResponse = (article, query) => {
    const title = article.title.replace(/^#\s*/, '');
    
    // Extract a relevant excerpt from the article content
    const content = article.content
      .replace(/#{1,6}\s+/g, '') // Remove headers
      .replace(/\*\*(.*?)\*\*/g, '$1') // Remove bold
      .replace(/\*(.*?)\*/g, '$1') // Remove italic
      .replace(/```[\s\S]*?```/g, '') // Remove code blocks
      .replace(/\n+/g, ' ') // Replace newlines with spaces
      .trim();

    const excerpt = content.length > 200 ? content.substring(0, 200) + '...' : content;

    return `📚 **${title}**\n\n${excerpt}\n\n💡 This should help answer your question about "${query}". Would you like me to provide more details or help you with something else?`;
  };

  const handleSuggestionClick = (suggestion) => {
    handleSendMessage(suggestion);
  };

  const handleKeyPress = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSendMessage();
    }
  };

  const toggleChat = () => {
    setIsOpen(!isOpen);
    if (!isOpen) {
      setIsMinimized(false);
    }
  };

  const toggleMinimize = () => {
    setIsMinimized(!isMinimized);
  };

  const formatTime = (timestamp) => {
    return new Date(timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  };

  if (!isOpen) {
    return (
      <div className="fixed bottom-4 right-4 z-50">
        <button
          onClick={toggleChat}
          className="bg-primary hover:bg-accent-1 text-white p-4 rounded-full shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105"
          data-chat-trigger
        >
          <FiHelpCircle className="h-6 w-6" />
        </button>
      </div>
    );
  }

  return (
    <div className="fixed bottom-4 right-4 z-50">
      <div className={`bg-white rounded-lg shadow-2xl border border-gray-200 transition-all duration-300 ${
        isMinimized ? 'w-80 h-12' : 'w-96 h-[500px]'
      }`}>
        {/* Header */}
        <div className="bg-gradient-to-r from-primary to-accent-1 text-white p-4 rounded-t-lg flex items-center justify-between">
          <div className="flex items-center">
            <FiHelpCircle className="h-5 w-5 mr-2" />
            <span className="font-semibold">Ardent POS Support</span>
          </div>
          <div className="flex items-center space-x-2">
            <button
              onClick={toggleMinimize}
              className="text-white hover:text-gray-200 transition-colors"
            >
              {isMinimized ? <FiMaximize2 className="h-4 w-4" /> : <FiMinimize2 className="h-4 w-4" />}
            </button>
            <button
              onClick={toggleChat}
              className="text-white hover:text-gray-200 transition-colors"
            >
              <FiX className="h-4 w-4" />
            </button>
          </div>
        </div>

        {!isMinimized && (
          <>
            {/* Messages */}
            <div className="flex-1 p-4 h-[380px] overflow-y-auto">
              <div className="space-y-4">
                {messages.map((message) => (
                  <div
                    key={message.id}
                    className={`flex ${message.type === 'user' ? 'justify-end' : 'justify-start'}`}
                  >
                    <div
                      className={`max-w-[80%] p-3 rounded-lg ${
                        message.type === 'user'
                          ? 'bg-primary text-white'
                          : 'bg-gray-100 text-gray-800'
                      }`}
                    >
                      <div className="flex items-start space-x-2">
                        {message.type === 'bot' && (
                          <FiHelpCircle className="h-4 w-4 mt-1 flex-shrink-0" />
                        )}
                        <div className="flex-1">
                          <div className="whitespace-pre-wrap text-sm">{message.content}</div>
                          {message.article && (
                            <div className="mt-2 p-2 bg-blue-50 rounded border-l-4 border-blue-400">
                              <div className="flex items-center text-xs text-blue-700">
                                <FiBookOpen className="h-3 w-3 mr-1" />
                                Knowledge Base Article
                              </div>
                            </div>
                          )}
                        </div>
                      </div>
                      <div className={`text-xs mt-1 ${
                        message.type === 'user' ? 'text-white/70' : 'text-gray-500'
                      }`}>
                        {formatTime(message.timestamp)}
                      </div>
                    </div>
                  </div>
                ))}
                
                {isTyping && (
                  <div className="flex justify-start">
                    <div className="bg-gray-100 text-gray-800 p-3 rounded-lg">
                      <div className="flex items-center space-x-1">
                        <FiHelpCircle className="h-4 w-4" />
                        <div className="flex space-x-1">
                          <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                          <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '0.1s' }}></div>
                          <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '0.2s' }}></div>
                        </div>
                      </div>
                    </div>
                  </div>
                )}
              </div>
              <div ref={messagesEndRef} />
            </div>

            {/* Suggested Questions */}
            {messages.length === 1 && (
              <div className="px-4 pb-2">
                <div className="text-xs text-gray-500 mb-2">Quick questions:</div>
                <div className="flex flex-wrap gap-1">
                  {suggestedQuestions.slice(0, 4).map((question, index) => (
                    <button
                      key={index}
                      onClick={() => handleSuggestionClick(question)}
                      className="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-1 rounded-full transition-colors"
                    >
                      {question}
                    </button>
                  ))}
                </div>
              </div>
            )}

            {/* Input */}
            <div className="p-4 border-t border-gray-200">
              <div className="flex items-center space-x-2">
                <div className="flex-1 relative">
                  <textarea
                    value={inputMessage}
                    onChange={(e) => setInputMessage(e.target.value)}
                    onKeyPress={handleKeyPress}
                    placeholder="Type your message..."
                    className="w-full p-2 border border-gray-300 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                    rows="1"
                    style={{ minHeight: '40px', maxHeight: '100px' }}
                  />
                </div>
                <button
                  onClick={() => handleSendMessage()}
                  disabled={!inputMessage.trim() || isTyping}
                  className="bg-primary hover:bg-accent-1 disabled:bg-gray-300 text-white p-2 rounded-lg transition-colors disabled:cursor-not-allowed"
                >
                  <FiSend className="h-4 w-4" />
                </button>
              </div>
              
              {/* Quick Actions */}
              <div className="flex items-center justify-between mt-2 text-xs text-gray-500">
                <div className="flex items-center space-x-3">
                  <button className="flex items-center hover:text-primary transition-colors">
                    <FiPaperclip className="h-3 w-3 mr-1" />
                    Attach
                  </button>
                  <button className="flex items-center hover:text-primary transition-colors">
                    <FiSmile className="h-3 w-3 mr-1" />
                    Emoji
                  </button>
                </div>
                <div className="flex items-center">
                  <FiClock className="h-3 w-3 mr-1" />
                  <span>24/7 Support</span>
                </div>
              </div>
            </div>
          </>
        )}
      </div>
    </div>
  );
};

export default ChatWidget;
