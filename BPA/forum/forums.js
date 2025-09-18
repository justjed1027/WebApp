const forums = [
  { title: "Math Forum", desc: "Discuss algebra, geometry, and calculus.", link: "forum-math.html" },
  { title: "English Forum", desc: "Talk about grammar, literature, and writing.", link: "forum-english.html" },
  { title: "Science Forum", desc: "Physics, chemistry, and biology discussions.", link: "forum-science.html" },
  { title: "General Discussion", desc: "Off-topic chats, announcements, and more.", link: "forum-general.html" }
];

const forumList = document.getElementById("forum-list");

forums.forEach(forum => {
  const card = document.createElement("div");
  card.className = "card";
  card.innerHTML = `
    <h2>${forum.title}</h2>
    <p>${forum.desc}</p>
  `;
  card.addEventListener("click", () => {
    window.location.href = forum.link;
  });
  forumList.appendChild(card);
});
