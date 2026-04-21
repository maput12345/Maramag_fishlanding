import javax.imageio.ImageIO;
import java.awt.BasicStroke;
import java.awt.Color;
import java.awt.Font;
import java.awt.FontMetrics;
import java.awt.GradientPaint;
import java.awt.Graphics2D;
import java.awt.Polygon;
import java.awt.RenderingHints;
import java.awt.geom.Ellipse2D;
import java.awt.geom.RoundRectangle2D;
import java.awt.image.BufferedImage;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.util.ArrayList;
import java.util.List;

public class SimpleContextDfdExporter {
    private static final Color BG_TOP = new Color(255, 255, 255);
    private static final Color BG_BOTTOM = new Color(246, 248, 251);
    private static final Color TEXT = new Color(30, 41, 59);
    private static final Color BORDER = new Color(107, 114, 128);
    private static final Color BOX_FILL = new Color(245, 245, 245);
    private static final Color PROCESS_FILL = new Color(236, 240, 245);
    private static final Color LINE = new Color(55, 65, 81);

    private static final Font TITLE_FONT = new Font("SansSerif", Font.BOLD, 24);
    private static final Font SHAPE_FONT = new Font("SansSerif", Font.BOLD, 18);
    private static final Font LABEL_FONT = new Font("SansSerif", Font.PLAIN, 17);
    private static final Font SMALL_FONT = new Font("SansSerif", Font.PLAIN, 14);

    public static void main(String[] args) throws Exception {
        Path outputDir = Paths.get("POS", "generated-diagrams");
        Files.createDirectories(outputDir);

        Path output = outputDir.resolve("simple_level0_context.png");
        ImageIO.write(drawDiagram(), "png", output.toFile());

        System.out.println("Generated:");
        System.out.println(output.toAbsolutePath());
    }

    private static BufferedImage drawDiagram() {
        int width = 1800;
        int height = 1100;
        BufferedImage image = new BufferedImage(width, height, BufferedImage.TYPE_INT_ARGB);
        Graphics2D g = image.createGraphics();
        g.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
        g.setRenderingHint(RenderingHints.KEY_TEXT_ANTIALIASING, RenderingHints.VALUE_TEXT_ANTIALIAS_ON);

        g.setPaint(new GradientPaint(0, 0, BG_TOP, 0, height, BG_BOTTOM));
        g.fillRect(0, 0, width, height);

        g.setColor(TEXT);
        g.setFont(TITLE_FONT);
        String title = "0 - Level DFD : Context Level";
        int titleWidth = g.getFontMetrics().stringWidth(title);
        g.drawString(title, (width - titleWidth) / 2, 70);

        g.setStroke(new BasicStroke(2f));
        g.drawLine(0, 105, width, 105);

        int boxW = 220;
        int boxH = 95;
        int leftX = 80;
        int rightX = width - 80 - boxW;
        int centerOvalX = 625;
        int centerOvalY = 280;
        int ovalW = 550;
        int ovalH = 250;

        drawBox(g, leftX, 320, boxW, boxH, "LEEO ADMIN");
        drawBox(g, rightX, 320, boxW, boxH, "LEEO STAFF");
        drawBox(g, 790, 740, boxW, boxH, "BROKER");
        drawProcess(g, centerOvalX, centerOvalY, ovalW, ovalH,
            "Point-of-Sale and Inventory\nManagement System"
        );

        int leftCenterY = 320 + boxH / 2;
        int rightCenterY = 320 + boxH / 2;
        int bottomCenterX = 790 + boxW / 2;
        int bottomY = 740;

        int ovalLeftX = centerOvalX;
        int ovalRightX = centerOvalX + ovalW;
        int ovalMidYLeft = centerOvalY + ovalH / 2 - 30;
        int ovalMidYRight = centerOvalY + ovalH / 2 - 10;
        int ovalBottomX = centerOvalX + ovalW / 2;
        int ovalBottomY = centerOvalY + ovalH;

        drawArrow(g, leftX + boxW, leftCenterY - 12, ovalLeftX, ovalMidYLeft);
        drawArrow(g, ovalLeftX, ovalMidYLeft + 62, leftX + boxW, leftCenterY + 18);
        drawLabel(g, 360, 330, "Login / Admin Requests");
        drawLabel(g, 390, 425, "Response / Reports");

        drawArrow(g, ovalRightX, ovalMidYRight, rightX, rightCenterY - 10);
        drawArrow(g, rightX, rightCenterY + 25, ovalRightX, ovalMidYRight + 72);
        drawLabel(g, 1260, 330, "Login / Monitoring Requests");
        drawLabel(g, 1285, 425, "Response / Reports");

        drawArrow(g, bottomCenterX, bottomY, ovalBottomX, ovalBottomY);
        drawArrow(g, ovalBottomX + 40, ovalBottomY, bottomCenterX + 40, bottomY);
        drawVerticalLabel(g, 915, 610, "Operational Data");
        drawVerticalLabel(g, 1010, 610, "System Response");

        g.setFont(SMALL_FONT);
        g.setColor(new Color(71, 85, 105));
        String note = "External entities: LEEO Admin, LEEO Staff, and Broker";
        g.drawString(note, 95, 1000);
        g.drawString("Main process: Point-of-Sale and Inventory Management System", 95, 1030);

        g.dispose();
        return image;
    }

    private static void drawBox(Graphics2D g, int x, int y, int w, int h, String text) {
        g.setColor(BOX_FILL);
        g.fill(new RoundRectangle2D.Double(x, y, w, h, 18, 18));
        g.setColor(BORDER);
        g.setStroke(new BasicStroke(2f));
        g.draw(new RoundRectangle2D.Double(x, y, w, h, 18, 18));
        drawCenteredText(g, text, x, y, w, h, SHAPE_FONT);
    }

    private static void drawProcess(Graphics2D g, int x, int y, int w, int h, String text) {
        g.setColor(PROCESS_FILL);
        g.fill(new Ellipse2D.Double(x, y, w, h));
        g.setColor(BORDER);
        g.setStroke(new BasicStroke(2.3f));
        g.draw(new Ellipse2D.Double(x, y, w, h));
        drawCenteredWrappedText(g, text, x + 30, y + 25, w - 60, h - 50, SHAPE_FONT);
    }

    private static void drawArrow(Graphics2D g, int x1, int y1, int x2, int y2) {
        g.setColor(LINE);
        g.setStroke(new BasicStroke(2f));
        g.drawLine(x1, y1, x2, y2);
        drawArrowHead(g, x1, y1, x2, y2);
    }

    private static void drawArrowHead(Graphics2D g, int x1, int y1, int x2, int y2) {
        double angle = Math.atan2(y2 - y1, x2 - x1);
        int size = 12;
        int xA = (int) (x2 - size * Math.cos(angle - Math.PI / 7));
        int yA = (int) (y2 - size * Math.sin(angle - Math.PI / 7));
        int xB = (int) (x2 - size * Math.cos(angle + Math.PI / 7));
        int yB = (int) (y2 - size * Math.sin(angle + Math.PI / 7));

        Polygon head = new Polygon();
        head.addPoint(x2, y2);
        head.addPoint(xA, yA);
        head.addPoint(xB, yB);
        g.fillPolygon(head);
    }

    private static void drawLabel(Graphics2D g, int x, int y, String text) {
        g.setColor(TEXT);
        g.setFont(LABEL_FONT);
        g.drawString(text, x, y);
    }

    private static void drawVerticalLabel(Graphics2D g, int x, int y, String text) {
        g.setColor(TEXT);
        g.setFont(LABEL_FONT);
        Graphics2D g2 = (Graphics2D) g.create();
        g2.rotate(-Math.PI / 2, x, y);
        g2.drawString(text, x, y);
        g2.dispose();
    }

    private static void drawCenteredText(Graphics2D g, String text, int x, int y, int w, int h, Font font) {
        g.setColor(TEXT);
        g.setFont(font);
        FontMetrics metrics = g.getFontMetrics(font);
        int tx = x + (w - metrics.stringWidth(text)) / 2;
        int ty = y + ((h - metrics.getHeight()) / 2) + metrics.getAscent();
        g.drawString(text, tx, ty);
    }

    private static void drawCenteredWrappedText(Graphics2D g, String text, int x, int y, int width, int height, Font font) {
        g.setColor(TEXT);
        g.setFont(font);
        FontMetrics metrics = g.getFontMetrics(font);
        List<String> lines = wrap(text, metrics, width);
        int lineHeight = metrics.getHeight();
        int totalHeight = lines.size() * lineHeight;
        int currentY = y + Math.max(0, (height - totalHeight) / 2) + metrics.getAscent();

        for (String line : lines) {
            int lineWidth = metrics.stringWidth(line);
            int currentX = x + Math.max(0, (width - lineWidth) / 2);
            g.drawString(line, currentX, currentY);
            currentY += lineHeight;
        }
    }

    private static List<String> wrap(String text, FontMetrics metrics, int maxWidth) {
        List<String> lines = new ArrayList<>();
        for (String rawLine : text.split("\\n")) {
            String[] words = rawLine.trim().split("\\s+");
            if (rawLine.trim().isEmpty()) {
                lines.add("");
                continue;
            }
            StringBuilder current = new StringBuilder();
            for (String word : words) {
                String candidate = current.length() == 0 ? word : current + " " + word;
                if (metrics.stringWidth(candidate) <= maxWidth) {
                    current.setLength(0);
                    current.append(candidate);
                } else {
                    if (current.length() > 0) {
                        lines.add(current.toString());
                    }
                    current.setLength(0);
                    current.append(word);
                }
            }
            if (current.length() > 0) {
                lines.add(current.toString());
            }
        }
        return lines;
    }
}
