import cv2, numpy as np, os

path = r"C:\xampp\htdocs\ai_classroom_cheat_detection\outputs\synthetic_gate5.mp4"
writer = cv2.VideoWriter(path, cv2.VideoWriter_fourcc(*"mp4v"), 2, (320, 240))
for i in range(10):
    img = np.full((240, 320, 3), int(30 + i * 20), dtype=np.uint8)
    writer.write(img)
writer.release()
print("Synthetic video:", path)
