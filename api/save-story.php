
async function saveStoryToServer(storyData) {
    const response = await fetch('/api/save-story.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(storyData)
    });
    
    return await response.json();
}

async function loadStoriesFromServer() {
    const response = await fetch('/api/save-story.php');
    return await response.json();
}
