document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".question-block-answers input[type='radio']").forEach(radio => {
        radio.addEventListener("change", function () {
            let parentDiv = this.closest(".form-check");

            parentDiv.closest(".question-block-answers").querySelectorAll(".form-check").forEach(div => {
                div.classList.remove("correct-answer", "wrong-answer");
                let icon = div.querySelector(".answer-icon");
                if (icon) {
                    icon.remove();
                }
                let input = div.querySelector("input");
                input.style.visibility = "visible";
            });

            let icon = document.createElement("span");
            icon.classList.add("answer-icon");
            if (this.dataset.correct === "true") {
                icon.classList.add("correct");
                let quizBlock = this.closest(".wp-block-goodblocks-quiz");
                quizBlock.classList.add("answered");

                let videoUrl = getComputedStyle(document.documentElement).getPropertyValue("--correct_background").trim();
                let type = getComputedStyle(document.documentElement).getPropertyValue("--correct_type").trim();

                if (type != "video") {
                    return
                }

                let existingVideo = quizBlock.querySelector(".quiz-background-video");
                if (existingVideo) {
                    existingVideo.remove();
                }

                let video = document.createElement("video");
                video.src = videoUrl;
                video.classList.add("quiz-background-video");
                video.autoplay = true;
                video.loop = true;
                video.muted = true;
                video.playsInline = true;

                quizBlock.appendChild(video);
                quizBlock.querySelectorAll("input").forEach(input => {
                    input.disabled = true;
                });
            } else {
                icon.classList.add("wrong");
            }

            this.style.visibility = "hidden";
            parentDiv.prepend(icon)
        });
    });
});
