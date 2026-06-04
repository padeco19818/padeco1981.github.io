// Types/Interfaces (for documentation, remove if using plain JavaScript)
/**
 * @typedef {Object} StoryData
 * @property {string} [id]
 * @property {string} title
 * @property {string} content
 * @property {Date|string} [createdAt]
 */

/**
 * Fetch with timeout and abort capability
 * @param {string} url - The endpoint URL
 * @param {Object} options - Fetch options
 * @param {number} timeout - Timeout in milliseconds
 * @returns {Promise<Response>}
 */
async function fetchWithTimeout(url, options = {}, timeout = 10000) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeout);
    
    try {
        const response = await fetch(url, { 
            ...options, 
            signal: controller.signal 
        });
        clearTimeout(timeoutId);
        return response;
    } catch (error) {
        clearTimeout(timeoutId);
        if (error.name === 'AbortError') {
            throw new Error(`Request timeout after ${timeout}ms`);
        }
        throw error;
    }
}

/**
 * Save a story to the server
 * @param {StoryData} storyData - The story data to save
 * @returns {Promise<Object>} - Server response
 */
async function saveStoryToServer(storyData) {
    try {
        // Validate input
        if (!storyData || typeof storyData !== 'object') {
            throw new Error('Invalid story data provided');
        }
        
        if (!storyData.title || !storyData.content) {
            throw new Error('Story must have both title and content');
        }
        
        const response = await fetchWithTimeout('/api/save-story.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(storyData)
        }, 15000); // 15 second timeout for save operations
        
        // Check if response is OK
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP ${response.status}: ${errorText || response.statusText}`);
        }
        
        // Parse and return JSON response
        const result = await response.json();
        
        // Validate server response
        if (!result || typeof result !== 'object') {
            throw new Error('Invalid server response');
        }
        
        return result;
        
    } catch (error) {
        console.error('Failed to save story:', error.message);
        
        // Return a standardized error object instead of throwing
        return {
            success: false,
            error: error.message,
            timestamp: new Date().toISOString()
        };
    }
}

/**
 * Load all stories from the server
 * @param {AbortSignal} [signal] - Optional abort signal for cancellation
 * @returns {Promise<Object>} - Server response with stories array
 */
async function loadStoriesFromServer(signal = null) {
    try {
        const options = {};
        if (signal) {
            options.signal = signal;
        }
        
        const response = await fetchWithTimeout('/api/save-story.php', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            },
            ...options
        }, 10000); // 10 second timeout for reads
        
        // Check if response is OK
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP ${response.status}: ${errorText || response.statusText}`);
        }
        
        // Parse and return JSON response
        const result = await response.json();
        
        // Validate response structure
        if (!result || typeof result !== 'object') {
            throw new Error('Invalid server response');
        }
        
        // Ensure stories is always an array
        if (!result.stories) {
            result.stories = [];
        }
        
        return result;
        
    } catch (error) {
        console.error('Failed to load stories:', error.message);
        
        // Return empty result on error
        return {
            success: false,
            error: error.message,
            stories: [],
            timestamp: new Date().toISOString()
        };
    }
}

// Usage examples with loading states and cancellation:

/**
 * Example: Save with loading state
 */
async function exampleSaveWithUI(storyData) {
    const saveButton = document.getElementById('saveButton');
    const statusDiv = document.getElementById('status');
    
    try {
        // Disable button and show loading
        saveButton.disabled = true;
        statusDiv.textContent = 'Saving...';
        statusDiv.className = 'loading';
        
        const result = await saveStoryToServer(storyData);
        
        if (result.success || result.id) {
            statusDiv.textContent = 'Story saved successfully!';
            statusDiv.className = 'success';
            return result;
        } else {
            throw new Error(result.error || 'Save failed');
        }
        
    } catch (error) {
        statusDiv.textContent = `Error: ${error.message}`;
        statusDiv.className = 'error';
        throw error;
        
    } finally {
        saveButton.disabled = false;
        setTimeout(() => {
            statusDiv.textContent = '';
            statusDiv.className = '';
        }, 3000);
    }
}

/**
 * Example: Load with abort controller
 */
function exampleLoadWithCancel() {
    const controller = new AbortController();
    const loadButton = document.getElementById('loadButton');
    const cancelButton = document.getElementById('cancelButton');
    
    async function load() {
        try {
            loadButton.disabled = true;
            cancelButton.disabled = false;
            
            const result = await loadStoriesFromServer(controller.signal);
            
            if (result.stories && result.stories.length > 0) {
                displayStories(result.stories);
            } else if (result.error) {
                console.warn('Warning:', result.error);
            }
            
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Load failed:', error);
            }
        } finally {
            loadButton.disabled = false;
            cancelButton.disabled = true;
        }
    }
    
    function cancel() {
        controller.abort();
        cancelButton.disabled = true;
    }
    
    return { load, cancel };
}

/**
 * Helper: Display stories in UI
 */
function displayStories(stories) {
    const container = document.getElementById('storiesContainer');
    if (!container) return;
    
    container.innerHTML = stories.map(story => `
        <div class="story-card">
            <h3>${escapeHtml(story.title)}</h3>
            <p>${escapeHtml(story.content)}</p>
            ${story.createdAt ? `<small>${new Date(story.createdAt).toLocaleDateString()}</small>` : ''}
        </div>
    `).join('');
}

/**
 * Helper: Escape HTML to prevent XSS
 */
function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// Export for module usage (if using modules)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        saveStoryToServer,
        loadStoriesFromServer,
        fetchWithTimeout,
        escapeHtml
    };
}
