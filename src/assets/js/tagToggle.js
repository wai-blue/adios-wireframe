function tagToggle(tag) {
  if (tag.className == "tag-deactive") {
    tag.className = "tag-active";
  } else {
    tag.className = "tag-deactive";
  }
}