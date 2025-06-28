
import java.io.BufferedReader;
import java.io.File;
import java.io.FileReader;
import java.io.IOException;
import java.util.ArrayList;

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */

/**
 *
 * @author jarmi
 */
public class ReadWriter {
    
    
    public static File getFile(String n)
	{
         String cestaPlocha = System.getProperty("user.home") + File.separator + "Desktop";
	 File f = new File(cestaPlocha + File.separator + n + ".txt");
		
	 if (!f.exists())
	 try
	 {
          f.createNewFile();
	 }
	 catch (IOException e)
	 {
	  e.printStackTrace();
	 }
         return f;
	}
    
    public static String[] readLines(File f)
	{	
		ArrayList<String> st = new ArrayList<>();
		
		try (BufferedReader r = new BufferedReader(new FileReader(f)))
		{
			String s = null;
			
			while ((s = r.readLine()) != null)
				st.add(s);
		}
		catch (IOException e)
		{
                    e.printStackTrace();
		}
		return st.toArray(new String[st.size()]);
	}
    
}
