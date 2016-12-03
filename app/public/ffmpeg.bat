FOR %%i IN (*.avi *.mkv) do ffmpeg.exe -i "%%i" -c:v libx264 -preset medium -qp 18 -c:a aac -b:a 128k -ar 44100 -f mp4 "%%i".mp4
