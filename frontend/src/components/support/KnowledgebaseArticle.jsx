import React, { useState } from 'react';
import {
  FiEye,
  FiThumbsUp,
  FiThumbsDown,
  FiClock,
  FiUser,
  FiTag,
  FiArrowRight,
  FiBookOpen
} from 'react-icons/fi';

const KnowledgebaseArticle = ({ article, viewMode, categoryName, categoryIcon, onClick }) => {
  const [isExpanded, setIsExpanded] = useState(false);

  const truncateText = (text, maxLength = 150) => {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
  };

  const stripMarkdown = (text) => {
    return text
      .replace(/#{1,6}\s+/g, '') // Remove headers
      .replace(/\*\*(.*?)\*\*/g, '$1') // Remove bold
      .replace(/\*(.*?)\*/g, '$1') // Remove italic
      .replace(/\[([^\]]+)\]\([^)]+\)/g, '$1') // Remove links
      .replace(/`([^`]+)`/g, '$1') // Remove inline code
      .replace(/```[\s\S]*?```/g, '') // Remove code blocks
      .replace(/\n+/g, ' ') // Replace newlines with spaces
      .trim();
  };

  const plainTextContent = stripMarkdown(article.content);

  if (viewMode === 'list') {
    return (
      <div className="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
        <div className="flex items-start justify-between">
          <div className="flex-1">
            <div className="flex items-center mb-3">
              {categoryIcon}
              <span className="ml-2 text-sm font-medium text-gray-600 bg-gray-100 px-2 py-1 rounded-full">
                {categoryName}
              </span>
            </div>
            
            <h3 className="text-xl font-semibold text-gray-900 mb-2 hover:text-primary transition-colors cursor-pointer">
              {article.title}
            </h3>
            
            <p className="text-gray-600 mb-4">
              {isExpanded ? plainTextContent : truncateText(plainTextContent, 200)}
            </p>
            
            {!isExpanded && plainTextContent.length > 200 && (
              <button
                onClick={() => setIsExpanded(true)}
                className="text-primary hover:text-accent-1 font-medium text-sm"
              >
                Read more
              </button>
            )}
            
            <div className="flex items-center justify-between text-sm text-gray-500 mt-4">
              <div className="flex items-center space-x-4">
                <span className="flex items-center">
                  <FiEye className="h-4 w-4 mr-1" />
                  {article.view_count} views
                </span>
                <span className="flex items-center">
                  <FiThumbsUp className="h-4 w-4 mr-1" />
                  {article.helpful_count} helpful
                </span>
                <span className="flex items-center">
                  <FiClock className="h-4 w-4 mr-1" />
                  {new Date(article.created_at).toLocaleDateString()}
                </span>
              </div>
              
              <button 
                onClick={onClick}
                className="text-primary hover:text-accent-1 font-medium flex items-center"
              >
                Read Article <FiArrowRight className="ml-1 h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  // Grid view
  return (
    <div className="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-all duration-200 hover:-translate-y-1">
      <div className="flex items-center mb-3">
        {categoryIcon}
        <span className="ml-2 text-sm font-medium text-gray-600 bg-gray-100 px-2 py-1 rounded-full">
          {categoryName}
        </span>
      </div>
      
      <h3 className="text-lg font-semibold text-gray-900 mb-3 hover:text-primary transition-colors cursor-pointer line-clamp-2">
        {article.title}
      </h3>
      
      <p className="text-gray-600 text-sm mb-4 line-clamp-3">
        {truncateText(plainTextContent, 120)}
      </p>
      
      <div className="flex items-center justify-between text-xs text-gray-500 mb-4">
        <div className="flex items-center space-x-3">
          <span className="flex items-center">
            <FiEye className="h-3 w-3 mr-1" />
            {article.view_count}
          </span>
          <span className="flex items-center">
            <FiThumbsUp className="h-3 w-3 mr-1" />
            {article.helpful_count}
          </span>
        </div>
        <span className="flex items-center">
          <FiClock className="h-3 w-3 mr-1" />
          {new Date(article.created_at).toLocaleDateString()}
        </span>
      </div>
      
      <div className="flex items-center justify-between">
        <div className="flex flex-wrap gap-1">
          {article.tags.split(',').slice(0, 2).map((tag, index) => (
            <span
              key={index}
              className="text-xs bg-primary/10 text-primary px-2 py-1 rounded-full"
            >
              {tag.trim()}
            </span>
          ))}
          {article.tags.split(',').length > 2 && (
            <span className="text-xs text-gray-500">
              +{article.tags.split(',').length - 2} more
            </span>
          )}
        </div>
        
        <button 
          onClick={onClick}
          className="text-primary hover:text-accent-1 font-medium text-sm flex items-center"
        >
          Read <FiArrowRight className="ml-1 h-3 w-3" />
        </button>
      </div>
    </div>
  );
};

export default KnowledgebaseArticle;
