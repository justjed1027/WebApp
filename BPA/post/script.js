// Simulated posts (later will come from PHP + DB)
const posts = [
  {
    id: 1,
    user: "Alex Johnson",
    time: "2 hours ago",
    field: "Computer Science",
    content: "Does anyone have experience implementing a binary search tree in JavaScript? I'm struggling with the delete operation."
  },
  {
    id: 2,
    user: "Morgan Lee",
    time: "5 hours ago",
    field: "UX Design",
    content: "Just finished this UI design guide for mobile applications. Hope this helps everyone working on app projects this semester!"
  }
];

// Render posts
function renderPosts() {
  const container = document.getElementById("posts-container");
  container.innerHTML = "";

  posts.forEach(post => {
    const postEl = document.createElement("div");
    postEl.className = "post";

    postEl.innerHTML = `
      <div class="post-header">
        <span class="post-user">${post.user} · <small>${post.field}</small></span>
        <span class="post-time">${post.time}</span>
      </div>
      <div class="post-content">
        <p>${post.content}</p>
      </div>
    `;

    container.appendChild(postEl);
  });
}

document.addEventListener("DOMContentLoaded", renderPosts);
