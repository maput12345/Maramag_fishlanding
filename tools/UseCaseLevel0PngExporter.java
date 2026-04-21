import javax.imageio.ImageIO;
import java.awt.BasicStroke;
import java.awt.Color;
import java.awt.Font;
import java.awt.FontMetrics;
import java.awt.GradientPaint;
import java.awt.Graphics2D;
import java.awt.Polygon;
import java.awt.RenderingHints;
import java.awt.Stroke;
import java.awt.geom.RoundRectangle2D;
import java.awt.image.BufferedImage;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.util.ArrayList;
import java.util.List;

public class UseCaseLevel0PngExporter {
    private static final Color BG = new Color(248, 250, 252);
    private static final Color TITLE = new Color(15, 23, 42);
    private static final Color SUBTITLE = new Color(71, 85, 105);
    private static final Color ENTITY_FILL = new Color(226, 232, 240);
    private static final Color ENTITY_BORDER = new Color(71, 85, 105);
    private static final Color PROCESS_FILL = new Color(219, 234, 254);
    private static final Color PROCESS_BORDER = new Color(37, 99, 235);
    private static final Color USECASE_FILL = new Color(255, 247, 237);
    private static final Color USECASE_BORDER = new Color(234, 88, 12);
    private static final Color LINE = new Color(51, 65, 85);
    private static final Color LABEL_BG = new Color(255, 255, 255, 238);
    private static final Color BOUNDARY = new Color(148, 163, 184);

    private static final Font TITLE_FONT = new Font("SansSerif", Font.BOLD, 34);
    private static final Font SUBTITLE_FONT = new Font("SansSerif", Font.PLAIN, 20);
    private static final Font SHAPE_TITLE_FONT = new Font("SansSerif", Font.BOLD, 20);
    private static final Font SHAPE_TEXT_FONT = new Font("SansSerif", Font.PLAIN, 17);
    private static final Font LABEL_FONT = new Font("SansSerif", Font.PLAIN, 15);
    private static final Font SMALL_FONT = new Font("SansSerif", Font.PLAIN, 14);
    private static final Stroke BOX_STROKE = new BasicStroke(2.2f);
    private static final Stroke LINE_STROKE = new BasicStroke(2.0f);

    public static void main(String[] args) throws Exception {
        Path outputDir = Paths.get("POS", "generated-diagrams");
        Files.createDirectories(outputDir);

        Path useCase = outputDir.resolve("use_case_exact.png");
        Path level0 = outputDir.resolve("level0_dfd_exact.png");
        Path sheet = outputDir.resolve("usecase_level0_sheet.png");

        BufferedImage useCaseImage = drawUseCaseDiagram();
        BufferedImage level0Image = drawLevel0Dfd();

        ImageIO.write(useCaseImage, "png", useCase.toFile());
        ImageIO.write(level0Image, "png", level0.toFile());
        ImageIO.write(buildSheet(useCaseImage, level0Image), "png", sheet.toFile());

        System.out.println("Generated:");
        System.out.println(useCase.toAbsolutePath());
        System.out.println(level0.toAbsolutePath());
        System.out.println(sheet.toAbsolutePath());
    }

    private static BufferedImage drawUseCaseDiagram() {
        int width = 2800;
        int height = 1950;
        BufferedImage image = canvas(width, height);
        Graphics2D g = graphics(image);

        drawHeader(
            g,
            width,
            "Use Case Diagram",
            "Point-of-Sale and Inventory Management System for Maramag Fish Landing"
        );

        g.setColor(BOUNDARY);
        g.setStroke(BOX_STROKE);
        g.draw(new RoundRectangle2D.Double(470, 170, 1860, 1570, 28, 28));
        g.setFont(SHAPE_TITLE_FONT);
        g.setColor(TITLE);
        g.drawString("Point-of-Sale and Inventory Management System for Maramag Fish Landing", 580, 210);

        drawStickFigure(g, 170, 500, "Broker");
        drawStickFigure(g, 2520, 370, "LEEO Admin");
        drawStickFigure(g, 2520, 860, "LEEO Staff");

        drawUseCase(g, 710, 300, 290, 90, "Log in");
        drawUseCase(g, 1080, 300, 320, 90, "Manage Profile");
        drawUseCase(g, 1480, 300, 380, 90, "View Broker Dashboard");

        drawUseCase(g, 690, 470, 330, 90, "Manage Fish Types");
        drawUseCase(g, 1080, 470, 320, 90, "Manage Fish Prices");
        drawUseCase(g, 1460, 470, 400, 90, "Manage Fish Boxes");

        drawUseCase(g, 690, 640, 330, 90, "Scan QR Code");
        drawUseCase(g, 1080, 640, 320, 90, "Record Sales Transaction");
        drawUseCase(g, 1460, 640, 400, 90, "Record Buyer Information");

        drawUseCase(g, 760, 810, 270, 90, "Record Payment");
        drawUseCase(g, 1100, 810, 280, 90, "Print Receipt");
        drawUseCase(g, 1450, 810, 410, 90, "View Monitoring Dashboard");

        drawUseCase(g, 760, 980, 270, 90, "Manage Users");
        drawUseCase(g, 1110, 980, 270, 90, "Monitor Sales\nand Reports");
        drawUseCase(g, 1450, 980, 410, 90, "Monitor Fish Box Tracking");

        connectActorLine(g, 240, 500, 710, 345);
        connectActorLine(g, 240, 500, 1080, 345);
        connectActorLine(g, 240, 500, 1480, 345);
        connectActorLine(g, 240, 500, 690, 515);
        connectActorLine(g, 240, 500, 1080, 515);
        connectActorLine(g, 240, 500, 1460, 515);
        connectActorLine(g, 240, 500, 690, 685);
        connectActorLine(g, 240, 500, 1080, 685);
        connectActorLine(g, 240, 500, 1460, 685);
        connectActorLine(g, 240, 500, 760, 855);
        connectActorLine(g, 240, 500, 1100, 855);

        connectActorLine(g, 2470, 370, 710 + 290, 345);
        connectActorLine(g, 2470, 370, 1080 + 320, 345);
        connectActorLine(g, 2470, 370, 1450 + 410, 855);
        connectActorLine(g, 2470, 370, 760 + 270, 1025);
        connectActorLine(g, 2470, 370, 1110 + 270, 1025);
        connectActorLine(g, 2470, 370, 1450 + 410, 1025);

        connectActorLine(g, 2470, 860, 710 + 290, 345);
        connectActorLine(g, 2470, 860, 1080 + 320, 345);
        connectActorLine(g, 2470, 860, 1450 + 410, 855);
        connectActorLine(g, 2470, 860, 1110 + 270, 1025);
        connectActorLine(g, 2470, 860, 1450 + 410, 1025);

        drawFooterNote(
            g,
            95,
            1820,
            "Broker performs operational tasks, LEEO Admin performs broader administrative monitoring, and LEEO Staff performs limited monitoring support."
        );

        g.dispose();
        return image;
    }

    private static BufferedImage drawLevel0Dfd() {
        int width = 2600;
        int height = 1500;
        BufferedImage image = canvas(width, height);
        Graphics2D g = graphics(image);

        drawHeader(
            g,
            width,
            "Level 0 Data Flow Diagram",
            "Context diagram based on the exact actor structure used in the paper"
        );

        drawEntityBox(g, 80, 460, 320, 150, "Broker");
        drawEntityBox(g, 2180, 350, 320, 150, "LEEO Admin");
        drawEntityBox(g, 2180, 740, 320, 150, "LEEO Staff");
        drawProcess(
            g,
            780,
            330,
            1000,
            540,
            "Point-of-Sale and Inventory\nManagement System\nfor Maramag Fish Landing"
        );

        drawArrow(g, 400, 520, 780, 520);
        drawArrow(g, 780, 650, 400, 650);

        drawArrow(g, 1780, 440, 2180, 440);
        drawArrow(g, 2180, 560, 1780, 560);

        drawArrow(g, 1780, 810, 2180, 810);
        drawArrow(g, 2180, 690, 1780, 690);

        drawLabel(
            g,
            460,
            380,
            300,
            120,
            "Login credentials,\nprofile information,\nfish type data,\nfish price data,\nfish box data,\nQR scan, buyer,\nsales, payment data"
        );
        drawLabel(
            g,
            460,
            700,
            305,
            110,
            "Authentication result,\nprofile details,\nfish type and price records,\nfish box details,\nQR result,\nsales confirmation,\nbalance, receipt,\nbroker dashboard"
        );
        drawLabel(
            g,
            1840,
            260,
            300,
            120,
            "Login credentials,\nprofile information,\nuser account management,\ndashboard requests,\nreport and monitoring requests"
        );
        drawLabel(
            g,
            1835,
            570,
            305,
            110,
            "Authentication result,\nprofile details,\nuser account records,\ndashboard summaries,\nsales reports,\nfish box tracking reports"
        );
        drawLabel(
            g,
            1845,
            900,
            285,
            100,
            "Authentication result,\nprofile details,\ndashboard summaries,\nsales monitoring reports,\nfish box tracking records"
        );
        drawLabel(
            g,
            1830,
            705,
            300,
            100,
            "Login credentials,\nprofile information,\ndashboard requests,\nreport and monitoring requests"
        );

        drawFooterNote(
            g,
            90,
            1350,
            "External entities: Broker, LEEO Admin, and LEEO Staff"
        );
        drawFooterNote(
            g,
            90,
            1385,
            "Main process: Point-of-Sale and Inventory Management System for Maramag Fish Landing"
        );

        g.dispose();
        return image;
    }

    private static BufferedImage buildSheet(BufferedImage useCase, BufferedImage level0) {
        int width = 2900;
        int gap = 70;
        int height = 160 + useCase.getHeight() + gap + level0.getHeight() + 100;
        BufferedImage sheet = canvas(width, height);
        Graphics2D g = graphics(sheet);

        g.setColor(TITLE);
        g.setFont(TITLE_FONT);
        g.drawString("Use Case Diagram and Level 0 DFD PNG Export", 90, 85);
        g.setColor(SUBTITLE);
        g.setFont(SUBTITLE_FONT);
        g.drawString("Ready for capstone paper insertion", 90, 118);

        int x = (width - useCase.getWidth()) / 2;
        int y = 150;
        g.drawImage(useCase, x, y, null);

        y += useCase.getHeight() + gap;
        x = (width - level0.getWidth()) / 2;
        g.drawImage(level0, x, y, null);

        g.dispose();
        return sheet;
    }

    private static BufferedImage canvas(int width, int height) {
        BufferedImage image = new BufferedImage(width, height, BufferedImage.TYPE_INT_ARGB);
        Graphics2D g = image.createGraphics();
        g.setPaint(new GradientPaint(0, 0, Color.WHITE, 0, height, BG));
        g.fillRect(0, 0, width, height);
        g.dispose();
        return image;
    }

    private static Graphics2D graphics(BufferedImage image) {
        Graphics2D g = image.createGraphics();
        g.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
        g.setRenderingHint(RenderingHints.KEY_TEXT_ANTIALIASING, RenderingHints.VALUE_TEXT_ANTIALIAS_ON);
        g.setRenderingHint(RenderingHints.KEY_RENDERING, RenderingHints.VALUE_RENDER_QUALITY);
        return g;
    }

    private static void drawHeader(Graphics2D g, int width, String title, String subtitle) {
        g.setColor(TITLE);
        g.setFont(TITLE_FONT);
        int titleWidth = g.getFontMetrics().stringWidth(title);
        g.drawString(title, (width - titleWidth) / 2, 65);

        g.setColor(SUBTITLE);
        g.setFont(SUBTITLE_FONT);
        int subtitleWidth = g.getFontMetrics().stringWidth(subtitle);
        g.drawString(subtitle, (width - subtitleWidth) / 2, 102);
    }

    private static void drawEntityBox(Graphics2D g, int x, int y, int w, int h, String text) {
        g.setColor(ENTITY_FILL);
        g.fillRoundRect(x, y, w, h, 22, 22);
        g.setColor(ENTITY_BORDER);
        g.setStroke(BOX_STROKE);
        g.drawRoundRect(x, y, w, h, 22, 22);
        drawCenteredWrappedText(g, text, x + 16, y + 16, w - 32, h - 32, SHAPE_TITLE_FONT, TITLE);
    }

    private static void drawProcess(Graphics2D g, int x, int y, int w, int h, String text) {
        g.setColor(PROCESS_FILL);
        g.fillRoundRect(x, y, w, h, 38, 38);
        g.setColor(PROCESS_BORDER);
        g.setStroke(BOX_STROKE);
        g.drawRoundRect(x, y, w, h, 38, 38);
        drawCenteredWrappedText(g, text, x + 24, y + 20, w - 48, h - 40, SHAPE_TITLE_FONT, TITLE);
    }

    private static void drawUseCase(Graphics2D g, int x, int y, int w, int h, String text) {
        g.setColor(USECASE_FILL);
        g.fillOval(x, y, w, h);
        g.setColor(USECASE_BORDER);
        g.setStroke(BOX_STROKE);
        g.drawOval(x, y, w, h);
        drawCenteredWrappedText(g, text, x + 20, y + 12, w - 40, h - 24, SHAPE_TEXT_FONT, TITLE);
    }

    private static void drawStickFigure(Graphics2D g, int centerX, int topY, String label) {
        g.setColor(TITLE);
        g.setStroke(new BasicStroke(3f));
        g.drawOval(centerX - 22, topY, 44, 44);
        g.drawLine(centerX, topY + 44, centerX, topY + 115);
        g.drawLine(centerX - 42, topY + 75, centerX + 42, topY + 75);
        g.drawLine(centerX, topY + 115, centerX - 36, topY + 170);
        g.drawLine(centerX, topY + 115, centerX + 36, topY + 170);
        drawCenteredWrappedText(g, label, centerX - 110, topY + 188, 220, 60, SHAPE_TEXT_FONT, TITLE);
    }

    private static void connectActorLine(Graphics2D g, int x1, int y1, int x2, int y2) {
        g.setColor(LINE);
        g.setStroke(LINE_STROKE);
        g.drawLine(x1, y1, x2, y2);
    }

    private static void drawArrow(Graphics2D g, int x1, int y1, int x2, int y2) {
        g.setColor(LINE);
        g.setStroke(LINE_STROKE);
        g.drawLine(x1, y1, x2, y2);
        drawArrowHead(g, x1, y1, x2, y2);
    }

    private static void drawArrowHead(Graphics2D g, int x1, int y1, int x2, int y2) {
        double angle = Math.atan2(y2 - y1, x2 - x1);
        int size = 11;
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

    private static void drawLabel(Graphics2D g, int x, int y, int w, int h, String text) {
        g.setColor(LABEL_BG);
        g.fillRoundRect(x, y, w, h, 16, 16);
        g.setColor(new Color(203, 213, 225));
        g.setStroke(new BasicStroke(1.2f));
        g.drawRoundRect(x, y, w, h, 16, 16);
        drawCenteredWrappedText(g, text, x + 12, y + 10, w - 24, h - 20, LABEL_FONT, TITLE);
    }

    private static void drawFooterNote(Graphics2D g, int x, int y, String text) {
        g.setColor(SUBTITLE);
        g.setFont(SMALL_FONT);
        g.drawString(text, x, y);
    }

    private static void drawCenteredWrappedText(
        Graphics2D g,
        String text,
        int x,
        int y,
        int width,
        int height,
        Font font,
        Color color
    ) {
        g.setFont(font);
        g.setColor(color);
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
