import React, { useState } from 'react';
import { FiBookOpen, FiClock, FiEye, FiThumbsUp, FiThumbsDown, FiTag } from 'react-icons/fi';

const KnowledgebaseArticle = ({ article, viewMode = 'grid' }) => {
  const [isHelpful, setIsHelpful] = useState(null);
  const [showFullContent, setShowFullContent] = useState(false);

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  const handleHelpful = async (helpful) => {
    if (isHelpful !== null) return; // Prevent multiple votes
    
    setIsHelpful(helpful);
    
    try {
      // TODO: Implement helpful/not helpful API endpoint
      console.log(`Marked article ${article.id} as ${helpful ? 'helpful' : 'not helpful'}`);
    } catch (error) {
      console.error('Error marking article as helpful:', error);
    }
  };

  const truncateText = (text, maxLength = 150) => {
    if (text.length <= maxLength) return text;
    return text.substr(0, maxLength) + '...';
  };

  const stripHtml = (html) => {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
  };

  if (viewMode === 'list') {
    return (
      <div className="bg-white rounded-lg shadow-sm border p-6 hover:shadow-md transition-shadow">
        <div className="flex items-start justify-between">
          <div className="flex-1">
            <div className="flex items-center space-x-2 mb-2">
              <FiBookOpen className="h-4 w-4 text-primary" />
              <span className="text-sm text-gray-500">{article.category_name}</span>
            </div>
            
            <h3 className="text-lg font-semibold text-gray-900 mb-2 hover:text-primary transition-colors cursor-pointer">
              {article.title}
            </h3>
            
            <p className="text-gray-600 mb-3">
              {showFullContent ? stripHtml(article.content) : truncateText(stripHtml(article.content))}
            </p>
            
            {!showFullContent && stripHtml(article.content).length > 150 && (
              <button
                onClick={() => setShowFullContent(true)}
                className="text-primary hover:text-primary/80 text-sm font-medium transition-colors"
              >
                Read more
              </button>
            )}
            
            {showFullContent && (
              <button
                onClick={() => setShowFullContent(false)}
                className="text-primary hover:text-primary/80 text-sm font-medium transition-colors"
              >
                Show less
              </button>
            )}
            
            <div className="flex items-center space-x-4 text-sm text-gray-500 mt-3">
              <span className="flex items-center">
                <FiClock className="mr-1 h-3 w-3" />
                {formatDate(article.created_at)}
              </span>
              <span className="flex items-center">
                <FiEye className="mr-1 h-3 w-3" />
                {article.view_count || 0} views
              </span>
              {article.tags && (
                <span className="flex items-center">
                  <FiTag className="mr-1 h-3 w-3" />
                  {article.tags.split(',').slice(0, 2).join(', ')}
                </span>
              )}
            </div>
          </div>
          
          <div className="ml-4 flex flex-col items-center space-y-2">
            <button
              onClick={() => handleHelpful(true)}
              disabled={isHelpful !== null}
              className={`p-2 rounded-full transition-colors ${
                isHelpful === true 
                  ? 'bg-green-100 text-green-600' 
                  : 'text-gray-400 hover:text-green-600 hover:bg-green-50'
              }`}
              title="Mark as helpful"
            >
              <FiThumbsUp className="h-4 w-4" />
            </button>
            <span className="text-xs text-gray-500">{article.helpful_count || 0}</span>
            
            <button
              onClick={() => handleHelpful(false)}
              disabled={isHelpful !== null}
              className={`p-2 rounded-full transition-colors ${
                isHelpful === false 
                  ? 'bg-red-100 text-red-600' 
                  : 'text-gray-400 hover:text-red-600 hover:bg-red-50'
              }`}
              title="Mark as not helpful"
            >
              <FiThumbsDown className="h-4 w-4" />
            </button>
            <span className="text-xs text-gray-500">{article.not_helpful_count || 0}</span>
          </div>
        </div>
      </div>
    );
  }

  // Grid view (default)
  return (
    <div className="bg-white rounded-lg shadow-sm border p-6 hover:shadow-md transition-shadow">
      <div className="flex items-center space-x-2 mb-3">
        <FiBookOpen className="h-4 w-4 text-primary" />
        <span className="text-sm text-gray-500">{article.category_name}</span>
      </div>
      
      <h3 className="text-lg font-semibold text-gray-900 mb-3 hover:text-primary transition-colors cursor-pointer line-clamp-2">
        {article.title}
      </h3>
      
      <p className="text-gray-600 mb-4 line-clamp-3">
        {truncateText(stripHtml(article.content), 120)}
      </p>
      
      <div className="flex items-center justify-between text-sm text-gray-500 mb-3">
        <span className="flex items-center">
          <FiClock className="mr-1 h-3 w-3" />
          {formatDate(article.created_at)}
        </span>
        <span className="flex items-center">
          <FiEye className="mr-1 h-3 w-3" />
          {article.view_count || 0}
        </span>
      </div>
      
      {article.tags && (
        <div className="flex items-center mb-4">
          <FiTag className="h-3 w-3 text-gray-400 mr-1" />
          <div className="flex flex-wrap gap-1">
            {article.tags.split(',').slice(0, 3).map((tag, index) => (
              <span
                key={index}
                className="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full"
              >
                {tag.trim()}
              </span>
            ))}
          </div>
        </div>
      )}
      
      <div className="flex items-center justify-between pt-3 border-t border-gray-100">
        <div className="flex items-center space-x-2">
          <button
            onClick={() => handleHelpful(true)}
            disabled={isHelpful !== null}
            className={`flex items-center space-x-1 px-2 py-1 rounded text-xs transition-colors ${
              isHelpful === true 
                ? 'bg-green-100 text-green-600' 
                : 'text-gray-400 hover:text-green-600 hover:bg-green-50'
            }`}
          >
            <FiThumbsUp className="h-3 w-3" />
            <span>{article.helpful_count || 0}</span>
          </button>
          
          <button
            onClick={() => handleHelpful(false)}
            disabled={isHelpful !== null}
            className={`flex items-center space-x-1 px-2 py-1 rounded text-xs transition-colors ${
              isHelpful === false 
                ? 'bg-red-100 text-red-600' 
                : 'text-gray-400 hover:text-red-600 hover:bg-red-50'
            }`}
          >
            <FiThumbsDown className="h-3 w-3" />
            <span>{article.not_helpful_count || 0}</span>
          </button>
        </div>
        
        <button className="text-primary hover:text-primary/80 text-sm font-medium transition-colors">
          Read Article
        </button>
      </div>
    </div>
  );
};

export default KnowledgebaseArticle;
